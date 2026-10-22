<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\CorrespondenceCategory;
use App\Models\CorrespondenceDocument;
use App\Models\CorrespondenceTemplate;
use App\Models\DocumentVersion;
use App\Models\ReferenceSequence;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Notifications\PortalNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Registry correspondence lifecycle.
 *
 * Drafts are versioned. Submission starts a pinned workflow version. Approval
 * and issuance are separate steps: only an approved document may be issued, and
 * an issued document can no longer be edited (Master Prompt §8).
 */
class CorrespondenceService
{
    public function __construct(private readonly WorkflowEngine $engine) {}

    /**
     * @param  array{category_id: int, template_id?: int|null, subject: string, recipient_user_id?: int|null, recipient_name?: string|null, body: string}  $data
     */
    public function create(User $originator, array $data): CorrespondenceDocument
    {
        $category = CorrespondenceCategory::query()
            ->whereKey($data['category_id'])
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($originator, $data, $category): CorrespondenceDocument {
            $year = (int) now()->year;
            $sequence = ReferenceSequence::next(
                'correspondence:'.$category->code,
                $year,
                CorrespondenceDocument::query()->where('correspondence_category_id', $category->id)->count(),
            );

            $reference = sprintf('NFLV/REG/%s/%d/%04d', strtoupper(str_replace('_', '/', $category->code)), $year, $sequence);

            $document = CorrespondenceDocument::query()->create([
                'reference_number' => $reference,
                'correspondence_category_id' => $category->id,
                'correspondence_template_id' => $data['template_id'] ?? null,
                'subject' => trim($data['subject']),
                'originator_id' => $originator->id,
                'recipient_user_id' => $data['recipient_user_id'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'status' => CorrespondenceDocument::STATUS_DRAFT,
                'current_version' => 1,
            ]);

            DocumentVersion::query()->create([
                'correspondence_document_id' => $document->id,
                'version' => 1,
                'body_html' => $data['body'],
                'created_by' => $originator->id,
                'created_at' => now(),
            ]);

            AuditLog::record('correspondence.created', $document, [
                'reference_number' => $reference,
                'category' => $category->code,
            ]);

            return $document;
        });
    }

    /**
     * Save an edit as a new immutable version.
     */
    public function revise(CorrespondenceDocument $document, User $actor, string $subject, string $body): CorrespondenceDocument
    {
        if (! $document->isEditable()) {
            throw ValidationException::withMessages([
                'document' => 'Only a draft or returned document can be edited. Issued documents are immutable.',
            ]);
        }

        return DB::transaction(function () use ($document, $actor, $subject, $body): CorrespondenceDocument {
            $locked = CorrespondenceDocument::query()->lockForUpdate()->findOrFail($document->id);
            $next = $locked->current_version + 1;

            $locked->update([
                'subject' => trim($subject),
                'current_version' => $next,
            ]);

            DocumentVersion::query()->create([
                'correspondence_document_id' => $locked->id,
                'version' => $next,
                'body_html' => $body,
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);

            AuditLog::record('correspondence.revised', $locked, [
                'reference_number' => $locked->reference_number,
                'version' => $next,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Submit for approval, or resubmit a returned document without changing the
     * workflow version it started on.
     */
    public function submit(CorrespondenceDocument $document, User $actor): CorrespondenceDocument
    {
        if (! in_array($document->status, [CorrespondenceDocument::STATUS_DRAFT, CorrespondenceDocument::STATUS_RETURNED], true)) {
            throw ValidationException::withMessages(['document' => 'This document has already been submitted.']);
        }

        $existing = $document->workflowInstance;

        if ($existing !== null && $existing->status === WorkflowInstance::STATUS_RETURNED) {
            $this->engine->resubmit($existing, $actor);
            $document->update(['status' => CorrespondenceDocument::STATUS_SUBMITTED]);
        } else {
            $definition = WorkflowDefinition::query()
                ->where('code', RegistryWorkflowCatalog::definitionCodeFor($document->category))
                ->where('is_active', true)
                ->first();

            if ($definition === null) {
                throw ValidationException::withMessages([
                    'workflow' => 'No approval workflow is configured for this correspondence category.',
                ]);
            }

            $instance = $this->engine->start($definition, 'correspondence', $document->id, $actor);

            $document->update([
                'workflow_instance_id' => $instance->id,
                'status' => CorrespondenceDocument::STATUS_UNDER_REVIEW,
            ]);
        }

        AuditLog::record('correspondence.submitted', $document, [
            'reference_number' => $document->reference_number,
        ]);

        return $document->fresh();
    }

    public function decide(
        CorrespondenceDocument $document,
        User $actor,
        string $action,
        ?string $comments,
    ): CorrespondenceDocument {
        $instance = $document->workflowInstance;

        if ($instance === null) {
            throw ValidationException::withMessages(['workflow' => 'This document has not been submitted for approval.']);
        }

        $originator = $document->originator;

        $instance = $this->engine->decide($instance, $actor, $action, $comments, $originator);

        $updates = match ($instance->status) {
            WorkflowInstance::STATUS_APPROVED => [
                'status' => CorrespondenceDocument::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ],
            WorkflowInstance::STATUS_REJECTED => ['status' => CorrespondenceDocument::STATUS_REJECTED],
            WorkflowInstance::STATUS_RETURNED => ['status' => CorrespondenceDocument::STATUS_RETURNED],
            default => ['status' => CorrespondenceDocument::STATUS_UNDER_REVIEW],
        };

        $document->update($updates);

        if ($originator !== null && $originator->id !== $actor->id) {
            $message = match ($action) {
                'return' => 'Your correspondence was returned for correction.',
                'reject' => 'Your correspondence was not approved.',
                'approve', 'recommend' => 'Your correspondence advanced in the approval chain.',
                default => 'Your correspondence was updated in the approval chain.',
            };

            PortalNotifier::send(
                $originator,
                'Correspondence '.$document->reference_number,
                $message,
                route('registry.correspondence.show', $document),
                'correspondence',
            );
        }

        return $document->fresh(['workflowInstance.currentStep']);
    }

    /**
     * Issue an approved document. Approval does not imply delivery; delivery is
     * recorded separately.
     */
    public function issue(CorrespondenceDocument $document, User $actor, string $deliveryStatus = CorrespondenceDocument::DELIVERY_NOT_SENT): CorrespondenceDocument
    {
        if ($document->status !== CorrespondenceDocument::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'document' => 'Only an approved document can be issued. Complete every required approval first.',
            ]);
        }

        $version = $document->latestVersion();

        if ($version === null) {
            throw ValidationException::withMessages(['document' => 'The document has no content to issue.']);
        }

        return DB::transaction(function () use ($document, $actor, $version, $deliveryStatus): CorrespondenceDocument {
            $locked = CorrespondenceDocument::query()->lockForUpdate()->findOrFail($document->id);

            $html = view('letters.correspondence', [
                'document' => $locked,
                'body' => $version->body_html,
                'recipientName' => $locked->recipient?->name ?? $locked->recipient_name ?? 'Staff member',
            ])->render();

            $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            $content = $pdf->output();
            $hash = hash('sha256', $content);
            $path = 'correspondence/'.now()->year.'/'.$locked->id.'_'.$hash.'.pdf';

            Storage::disk('private')->put($path, $content);

            $locked->update([
                'status' => CorrespondenceDocument::STATUS_ISSUED,
                'issued_by' => $actor->id,
                'issued_at' => now(),
                'delivery_status' => $deliveryStatus,
                'storage_path' => $path,
                'file_hash' => $hash,
                'file_size' => strlen($content),
            ]);

            AuditLog::record('correspondence.issued', $locked, [
                'reference_number' => $locked->reference_number,
                'file_hash' => $hash,
                'delivery_status' => $deliveryStatus,
            ]);

            if ($locked->recipient !== null) {
                PortalNotifier::send(
                    $locked->recipient,
                    'Official correspondence available',
                    'A document addressed to you is available: '.$locked->reference_number.'.',
                    route('registry.correspondence.show', $locked),
                    'correspondence',
                );
            }

            return $locked->fresh();
        });
    }

    public function withdraw(CorrespondenceDocument $document, User $actor, string $reason): CorrespondenceDocument
    {
        if ($document->isIssued() || $document->status === CorrespondenceDocument::STATUS_SUPERSEDED) {
            throw ValidationException::withMessages([
                'document' => 'An issued document cannot be withdrawn silently. Issue a superseding correction instead.',
            ]);
        }

        $document->update([
            'status' => CorrespondenceDocument::STATUS_WITHDRAWN,
            'withdrawn_at' => now(),
        ]);

        AuditLog::record('correspondence.withdrawn', $document, [
            'reference_number' => $document->reference_number,
            'reason' => $reason,
        ]);

        return $document->fresh();
    }

    /**
     * @return array<string, string>
     */
    public static function placeholderValues(User $recipient, string $body): array
    {
        return [
            'staff_name' => $recipient->name,
            'staff_number' => (string) $recipient->staff_number,
            'year' => (string) now()->year,
            'body' => $body,
        ];
    }

    public static function renderTemplate(CorrespondenceTemplate $template, array $values): array
    {
        return [
            'subject' => $template->render($template->subject_template, $values),
            'body' => $template->render($template->body_template, $values),
        ];
    }
}
