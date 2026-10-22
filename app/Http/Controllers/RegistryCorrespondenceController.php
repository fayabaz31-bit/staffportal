<?php

namespace App\Http\Controllers;

use App\DutyCatalog;
use App\Models\AuditLog;
use App\Models\CorrespondenceCategory;
use App\Models\CorrespondenceDocument;
use App\Models\CorrespondenceTemplate;
use App\Models\DocumentAttachment;
use App\Models\User;
use App\Support\CorrespondenceService;
use App\Support\WorkflowEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Registry correspondence: preparation, versioning, approval and issuance.
 *
 * Access is record-level. A technical administrator does not automatically gain
 * access to confidential personnel correspondence (Master Prompt §12).
 */
class RegistryCorrespondenceController extends Controller
{
    public function __construct(
        private readonly CorrespondenceService $correspondence,
        private readonly WorkflowEngine $workflow,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        $query = CorrespondenceDocument::query()
            ->with(['category', 'originator', 'recipient', 'workflowInstance.currentStep'])
            ->latest('id');

        $this->scopeVisibleTo($query, $user);

        if (array_key_exists($status, CorrespondenceDocument::statusLabels())) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('subject', 'like', '%'.$search.'%')
                    ->orWhere('reference_number', 'like', '%'.$search.'%');
            });
        }

        return view('registry.correspondence.index', [
            'documents' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'search' => $search,
            'statusLabels' => CorrespondenceDocument::statusLabels(),
            'canCreate' => $user->can('manage-correspondence'),
            'awaitingMe' => $this->awaitingCount($user),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage-correspondence');

        $template = null;
        $templateId = $request->integer('template');

        if ($templateId !== 0) {
            $template = CorrespondenceTemplate::query()->with('category')->whereKey($templateId)->first();
        }

        return view('registry.correspondence.create', [
            'categories' => CorrespondenceCategory::query()->active()->ordered()->get(),
            'templates' => CorrespondenceTemplate::query()->with('category')->where('is_active', true)->orderBy('name')->get(),
            'recipients' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'staff_number']),
            'selectedTemplate' => $template,
            'placeholderValues' => [
                'staff_name' => '{{ staff_name }}',
                'staff_number' => '{{ staff_number }}',
                'year' => '{{ year }}',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-correspondence');

        $validated = $request->validate([
            'correspondence_category_id' => ['required', Rule::exists('correspondence_categories', 'id')->where('is_active', true)],
            'correspondence_template_id' => ['nullable', Rule::exists('correspondence_templates', 'id')],
            'recipient_user_id' => ['nullable', Rule::exists('users', 'id')->where('status', 'active')],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $document = $this->correspondence->create($request->user(), [
            'category_id' => (int) $validated['correspondence_category_id'],
            'template_id' => $validated['correspondence_template_id'] ?? null,
            'recipient_user_id' => $validated['recipient_user_id'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
        ]);

        return redirect()->route('registry.correspondence.show', $document)
            ->with('status', 'Draft created with reference '.$document->reference_number.'.');
    }

    public function show(CorrespondenceDocument $correspondence): View
    {
        $user = request()->user();

        abort_unless($this->canView($user, $correspondence), 403);

        $correspondence->load([
            'category',
            'template',
            'originator',
            'recipient',
            'versions.author',
            'attachments.uploader',
            'workflowInstance.currentStep',
            'workflowInstance.version.steps',
            'workflowInstance.actions.actor',
            'workflowInstance.actions.step',
        ]);

        return view('registry.correspondence.show', [
            'document' => $correspondence,
            'latestVersion' => $correspondence->latestVersion(),
            'canEdit' => $user->id === $correspondence->originator_id && $correspondence->isEditable(),
            'canSubmit' => $user->id === $correspondence->originator_id
                && in_array($correspondence->status, [CorrespondenceDocument::STATUS_DRAFT, CorrespondenceDocument::STATUS_RETURNED], true),
            'canDecide' => $correspondence->workflowInstance !== null
                && $this->workflow->canAct($correspondence->workflowInstance, $user, $correspondence->originator),
            'canIssue' => $user->can('issue-correspondence')
                && $correspondence->status === CorrespondenceDocument::STATUS_APPROVED,
            'canWithdraw' => $user->id === $correspondence->originator_id
                && ! $correspondence->isIssued()
                && $correspondence->status !== CorrespondenceDocument::STATUS_WITHDRAWN,
        ]);
    }

    public function revise(Request $request, CorrespondenceDocument $correspondence): RedirectResponse
    {
        abort_unless($request->user()->id === $correspondence->originator_id, 403);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
        ]);

        $this->correspondence->revise($correspondence, $request->user(), $validated['subject'], $validated['body']);

        return back()->with('status', 'Saved as a new document version.');
    }

    public function submit(Request $request, CorrespondenceDocument $correspondence): RedirectResponse
    {
        abort_unless($request->user()->id === $correspondence->originator_id, 403);

        $this->correspondence->submit($correspondence, $request->user());

        return back()->with('status', 'Submitted into the configured approval chain.');
    }

    public function decide(Request $request, CorrespondenceDocument $correspondence): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:review,recommend,approve,reject,return,escalate,forward'],
            'comments' => ['nullable', 'string', 'max:3000'],
        ]);

        $updated = $this->correspondence->decide(
            $correspondence,
            $request->user(),
            $validated['action'],
            $validated['comments'] ?? null,
        );

        return back()->with('status', 'Decision recorded. Document status: '.$updated->statusLabel().'.');
    }

    public function issue(Request $request, CorrespondenceDocument $correspondence): RedirectResponse
    {
        Gate::authorize('issue-correspondence');

        $validated = $request->validate([
            'delivery_status' => ['required', Rule::in([
                CorrespondenceDocument::DELIVERY_NOT_SENT,
                CorrespondenceDocument::DELIVERY_HANDED_OVER,
                CorrespondenceDocument::DELIVERY_COLLECTED,
                CorrespondenceDocument::DELIVERY_POSTED,
            ])],
        ]);

        $issued = $this->correspondence->issue($correspondence, $request->user(), $validated['delivery_status']);

        return back()->with('status', 'Issued as '.$issued->reference_number.'.');
    }

    public function withdraw(Request $request, CorrespondenceDocument $correspondence): RedirectResponse
    {
        abort_unless($request->user()->id === $correspondence->originator_id, 403);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->correspondence->withdraw($correspondence, $request->user(), $validated['reason']);

        return back()->with('status', 'Document withdrawn.');
    }

    public function download(CorrespondenceDocument $correspondence): StreamedResponse
    {
        abort_unless($this->canView(request()->user(), $correspondence), 403);

        if ($correspondence->storage_path === null || ! Storage::disk('private')->exists($correspondence->storage_path)) {
            abort(404, 'The issued document is not available yet.');
        }

        AuditLog::record('correspondence.downloaded', $correspondence, [
            'reference_number' => $correspondence->reference_number,
        ]);

        return Storage::disk('private')->download(
            $correspondence->storage_path,
            str_replace('/', '-', $correspondence->reference_number).'.pdf',
        );
    }

    public function uploadAttachment(Request $request, CorrespondenceDocument $correspondence): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->id === $correspondence->originator_id && $correspondence->isEditable(), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'extensions:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $file = $validated['file'];
        $path = $file->store('correspondence/attachments/'.$correspondence->id, 'private');

        if ($path === false) {
            return back()->withErrors(['file' => 'The attachment could not be stored securely.']);
        }

        DocumentAttachment::query()->create([
            'correspondence_document_id' => $correspondence->id,
            'document_version_id' => $correspondence->latestVersion()?->id,
            'uploaded_by' => $user->id,
            'original_name' => basename($file->getClientOriginalName()),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', Storage::disk('private')->path($path)),
            'created_at' => now(),
        ]);

        AuditLog::record('correspondence.attachment_uploaded', $correspondence, [
            'reference_number' => $correspondence->reference_number,
            'original_name' => basename($file->getClientOriginalName()),
        ]);

        return back()->with('status', 'Attachment stored privately against this document.');
    }

    public function downloadAttachment(CorrespondenceDocument $correspondence, DocumentAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->correspondence_document_id === $correspondence->id, 404);
        abort_unless($this->canView(request()->user(), $correspondence), 403);

        if (! Storage::disk('private')->exists($attachment->storage_path)) {
            abort(404, 'The attachment is no longer available.');
        }

        AuditLog::record('correspondence.attachment_downloaded', $correspondence, [
            'reference_number' => $correspondence->reference_number,
            'attachment_id' => $attachment->id,
        ]);

        return Storage::disk('private')->download($attachment->storage_path, $attachment->original_name);
    }

    private function canView(User $user, CorrespondenceDocument $document): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($user->id === $document->originator_id || $user->id === $document->recipient_user_id) {
            return true;
        }

        if ($document->workflowInstance !== null
            && $this->workflow->canAct($document->workflowInstance, $user, $document->originator)) {
            return true;
        }

        return $user->hasDuty(
            DutyCatalog::REGISTRY_HEAD_REVIEW,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
            DutyCatalog::REGISTRY_DIRECTOR_ESCALATION,
        );
    }

    /**
     * @param  Builder<CorrespondenceDocument>  $query
     */
    private function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where('originator_id', $user->id)
                ->orWhere('recipient_user_id', $user->id);

            if ($user->hasDuty(
                DutyCatalog::REGISTRY_HEAD_REVIEW,
                DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
                DutyCatalog::REGISTRY_DIRECTOR_ESCALATION,
            )) {
                $query->orWhereIn('status', [
                    CorrespondenceDocument::STATUS_SUBMITTED,
                    CorrespondenceDocument::STATUS_UNDER_REVIEW,
                    CorrespondenceDocument::STATUS_AWAITING_APPROVAL,
                    CorrespondenceDocument::STATUS_APPROVED,
                ]);
            }
        });
    }

    private function awaitingCount(User $user): int
    {
        if (! $user->hasDuty(
            DutyCatalog::REGISTRY_HEAD_REVIEW,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
            DutyCatalog::REGISTRY_DIRECTOR_ESCALATION,
        )) {
            return 0;
        }

        return CorrespondenceDocument::query()
            ->whereIn('status', [CorrespondenceDocument::STATUS_SUBMITTED, CorrespondenceDocument::STATUS_UNDER_REVIEW])
            ->count();
    }
}
