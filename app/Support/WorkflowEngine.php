<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Configurable approval engine.
 *
 * A definition is versioned; an instance pins the version it started on, so
 * editing a workflow never rewrites the chain for work already in progress.
 * Authority is resolved from the configured step, not from controller code.
 */
class WorkflowEngine
{
    /**
     * @var list<string>
     */
    private const DECISION_ACTIONS = ['review', 'recommend', 'approve', 'reject', 'return', 'escalate', 'forward'];

    public function start(WorkflowDefinition $definition, string $documentType, int $documentId, User $actor): WorkflowInstance
    {
        $version = $this->effectiveVersion($definition);

        if ($version === null) {
            throw ValidationException::withMessages([
                'workflow' => 'No published approval workflow is in force. Ask an administrator to configure one.',
            ]);
        }

        $firstStep = $version->steps()->orderBy('sequence')->first();

        if ($firstStep === null) {
            throw ValidationException::withMessages([
                'workflow' => 'The approval workflow has no configured steps.',
            ]);
        }

        return DB::transaction(function () use ($definition, $version, $firstStep, $documentType, $documentId, $actor): WorkflowInstance {
            $instance = WorkflowInstance::query()->create([
                'workflow_definition_id' => $definition->id,
                'workflow_version_id' => $version->id,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'current_step_id' => $firstStep->id,
                'status' => WorkflowInstance::STATUS_IN_PROGRESS,
                'started_by' => $actor->id,
            ]);

            $this->record($instance, $firstStep, $actor, 'submit', null, null, WorkflowInstance::STATUS_IN_PROGRESS);

            AuditLog::record('workflow.started', $instance, [
                'definition' => $definition->code,
                'version' => $version->version,
                'document_type' => $documentType,
                'document_id' => $documentId,
            ]);

            return $instance;
        });
    }

    /**
     * The published version in force today.
     */
    public function effectiveVersion(WorkflowDefinition $definition): ?WorkflowVersion
    {
        $today = now()->toDateString();

        return $definition->versions()
            ->where('is_published', true)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', $today);
            })
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
            })
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Whether the actor may act on the instance's current step.
     */
    public function canAct(WorkflowInstance $instance, User $actor, ?User $originator = null): bool
    {
        $step = $instance->currentStep;

        if ($step === null || $instance->status !== WorkflowInstance::STATUS_IN_PROGRESS) {
            return false;
        }

        if ($originator !== null && $originator->id === $actor->id) {
            return false;
        }

        return $this->stepAuthorises($step, $actor, $originator);
    }

    /**
     * Record a decision and advance, close or return the instance.
     *
     * @param  array<string, mixed>  $options
     */
    public function decide(
        WorkflowInstance $instance,
        User $actor,
        string $action,
        ?string $comments = null,
        ?User $originator = null,
        array $options = [],
    ): WorkflowInstance {
        if (! in_array($action, self::DECISION_ACTIONS, true)) {
            throw ValidationException::withMessages(['action' => 'That approval action is not recognised.']);
        }

        if (in_array($action, ['reject', 'return', 'escalate'], true) && trim((string) $comments) === '') {
            throw ValidationException::withMessages(['comments' => 'A comment is required for this action.']);
        }

        return DB::transaction(function () use ($instance, $actor, $action, $comments, $originator, $options): WorkflowInstance {
            $locked = WorkflowInstance::query()->lockForUpdate()->findOrFail($instance->id);
            $locked->loadMissing('currentStep');

            if ($locked->status !== WorkflowInstance::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages(['workflow' => 'This workflow has already been closed.']);
            }

            $step = $locked->currentStep;

            if ($step === null) {
                throw ValidationException::withMessages(['workflow' => 'This workflow has no current step.']);
            }

            if ($originator !== null && $originator->id === $actor->id) {
                throw ValidationException::withMessages(['workflow' => 'Separation of duties: you cannot approve or recommend your own document.']);
            }

            if (! $this->stepAuthorises($step, $actor, $originator)) {
                abort(403, 'You are not authorised to act at this workflow stage.');
            }

            $this->assertActionAllowed($step, $action);

            $from = $locked->status;

            if ($action === 'reject') {
                $locked->update([
                    'status' => WorkflowInstance::STATUS_REJECTED,
                    'completed_at' => now(),
                ]);
            } elseif ($action === 'return') {
                $locked->update(['status' => WorkflowInstance::STATUS_RETURNED]);
            } elseif ($action === 'approve' && ($options['close_on_approve'] ?? false)) {
                $locked->update([
                    'status' => WorkflowInstance::STATUS_APPROVED,
                    'completed_at' => now(),
                ]);
            } else {
                $next = $this->nextStep($locked, $step);

                if ($next === null) {
                    $locked->update([
                        'status' => WorkflowInstance::STATUS_APPROVED,
                        'completed_at' => now(),
                    ]);
                } else {
                    $locked->update(['current_step_id' => $next->id]);
                }
            }

            $this->record($locked, $step, $actor, $action, $comments, $from, $locked->status);

            AuditLog::record('workflow.'.$action, $locked, [
                'instance_id' => $locked->id,
                'step' => $step->code,
                'status' => $locked->status,
            ]);

            return $locked->fresh(['currentStep', 'actions.actor', 'actions.step']);
        });
    }

    /**
     * Re-open a returned instance at the step where it was returned, so a
     * corrected document resumes the same configured chain.
     */
    public function resubmit(WorkflowInstance $instance, User $actor): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $actor): WorkflowInstance {
            $locked = WorkflowInstance::query()->lockForUpdate()->findOrFail($instance->id);

            if ($locked->status !== WorkflowInstance::STATUS_RETURNED) {
                throw ValidationException::withMessages(['workflow' => 'Only a returned workflow can be resubmitted.']);
            }

            $from = $locked->status;
            $locked->update(['status' => WorkflowInstance::STATUS_IN_PROGRESS]);

            $this->record($locked, $locked->currentStep, $actor, 'resubmit', null, $from, $locked->status);

            return $locked;
        });
    }

    private function stepAuthorises(WorkflowStep $step, User $actor, ?User $originator): bool
    {
        if ($actor->status !== 'active') {
            return false;
        }

        return match ($step->approver_type) {
            WorkflowStep::APPROVER_DUTY => $step->approver_duty !== null && $actor->hasDuty($step->approver_duty),
            WorkflowStep::APPROVER_ROLE => $step->approver_role !== null && $actor->role->value === $step->approver_role,
            WorkflowStep::APPROVER_USER => $step->approver_user_id === $actor->id,
            WorkflowStep::APPROVER_SUPERVISOR => $originator !== null && $actor->isSupervisorOf($originator),
            default => false,
        };
    }

    private function assertActionAllowed(WorkflowStep $step, string $action): void
    {
        $allowed = match ($step->action_required) {
            WorkflowStep::ACTION_APPROVE => ['approve', 'reject', 'return', 'escalate'],
            WorkflowStep::ACTION_RECOMMEND => ['recommend', 'reject', 'return', 'escalate', 'forward'],
            WorkflowStep::ACTION_ISSUE => ['approve', 'reject'],
            default => ['review', 'approve', 'reject', 'return', 'escalate', 'forward'],
        };

        if (! in_array($action, $allowed, true)) {
            throw ValidationException::withMessages([
                'action' => 'The configured stage "'.$step->name.'" does not permit that action.',
            ]);
        }
    }

    private function nextStep(WorkflowInstance $instance, WorkflowStep $step): ?WorkflowStep
    {
        return WorkflowStep::query()
            ->where('workflow_version_id', $instance->workflow_version_id)
            ->where('sequence', '>', $step->sequence)
            ->orderBy('sequence')
            ->first();
    }

    private function record(
        WorkflowInstance $instance,
        ?WorkflowStep $step,
        User $actor,
        string $action,
        ?string $comments,
        ?string $from,
        string $to,
    ): void {
        WorkflowAction::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step?->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'comments' => $comments,
            'from_status' => $from,
            'to_status' => $to,
            'created_at' => now(),
        ]);
    }
}
