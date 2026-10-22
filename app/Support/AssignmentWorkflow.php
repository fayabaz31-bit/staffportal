<?php

namespace App\Support;

use App\DutyCatalog;
use App\Models\AssignmentEvent;
use App\Models\AuditLog;
use App\Models\OrganisationalUnit;
use App\Models\ReportingRelationship;
use App\Models\User;
use App\Models\WorkAssignment;
use App\Notifications\PortalNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Server-side state machine for assigned work.
 *
 * Every transition is validated here; a route, button or hand-crafted request
 * cannot move an assignment into a status the actor is not entitled to reach
 * (Master Prompt §6/§7). A completed duty never implies that a related official
 * document was approved.
 */
class AssignmentWorkflow
{
    /**
     * Actions an officer may take on their own assignment.
     *
     * @var list<string>
     */
    private const ASSIGNEE_ACTIONS = ['acknowledge', 'start', 'resume', 'submit'];

    /**
     * Actions only a supervisor may take.
     *
     * @var list<string>
     */
    private const SUPERVISOR_ACTIONS = ['return', 'complete', 'cancel', 'hold', 'reassign', 'extend'];

    /**
     * Actions any officer who can view the assignment may take.
     *
     * @var list<string>
     */
    private const VIEWER_ACTIONS = ['comment'];

    public function __construct(private readonly WorkAssignment $assignment) {}

    public static function canView(User $actor, WorkAssignment $assignment): bool
    {
        return $actor->id === $assignment->assigned_to
            || $actor->id === $assignment->assigned_by
            || self::canSupervise($actor, $assignment);
    }

    /**
     * Whether the actor may review, correct or close the assignment.
     *
     * A supervisor is the assigning officer, an officer with duty-management
     * authority over the assignee, the configured unit head, or the assignee's
     * current line manager. The assignee can never supervise their own work.
     */
    public static function canSupervise(User $actor, WorkAssignment $assignment): bool
    {
        if ($actor->status !== 'active' || $actor->id === $assignment->assigned_to) {
            return false;
        }

        if ($actor->id === $assignment->assigned_by) {
            return true;
        }

        $assignee = $assignment->assignee;

        if ($assignee === null) {
            return false;
        }

        if ($actor->hasDuty(DutyCatalog::ACCOUNT_MANAGE)) {
            return true;
        }

        if ($assignee->canManageDutiesOf($actor)) {
            return true;
        }

        if ($actor->isSupervisorOf($assignee)) {
            return true;
        }

        if ($assignment->organisational_unit_id !== null) {
            return OrganisationalUnit::query()
                ->whereKey($assignment->organisational_unit_id)
                ->where('head_user_id', $actor->id)
                ->exists();
        }

        return false;
    }

    /**
     * Whether the actor may create an assignment for the target officer.
     */
    public static function canAssign(User $actor, User $target): bool
    {
        if ($actor->status !== 'active' || $actor->id === $target->id) {
            return false;
        }

        if ($actor->hasDuty(DutyCatalog::ACCOUNT_MANAGE)) {
            return true;
        }

        if ($target->canManageDutiesOf($actor)) {
            return true;
        }

        return $actor->isSupervisorOf($target);
    }

    /**
     * Active officers whose work this actor may supervise.
     *
     * Combines configured reporting lines with duty-management scope, so a Head
     * of HR sees the personnel officers they lead even before reporting lines
     * have been configured individually.
     *
     * @return list<int>
     */
    public static function supervisedUserIds(User $actor): array
    {
        if ($actor->status !== 'active') {
            return [];
        }

        if ($actor->hasDuty(DutyCatalog::ACCOUNT_MANAGE)) {
            return User::query()
                ->where('status', 'active')
                ->whereKeyNot($actor->getKey())
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        $ids = ReportingRelationship::query()
            ->effectiveOn()
            ->where('supervisor_id', $actor->id)
            ->pluck('subordinate_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($actor->isDutyLead()) {
            $managed = User::query()
                ->where('status', 'active')
                ->whereKeyNot($actor->getKey())
                ->with('staffProfile')
                ->get()
                ->filter(fn (User $candidate): bool => $candidate->canManageDutiesOf($actor))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $ids = [...$ids, ...$managed];
        }

        return array_values(array_unique($ids));
    }

    /**
     * Apply an action, recording the status history and notifying the people
     * affected. Returns the refreshed assignment.
     *
     * @param  array<string, mixed>  $data
     */
    public function apply(User $actor, string $action, array $data = []): WorkAssignment
    {
        if (! in_array($action, [...self::ASSIGNEE_ACTIONS, ...self::SUPERVISOR_ACTIONS, ...self::VIEWER_ACTIONS], true)) {
            throw ValidationException::withMessages(['action' => 'That action is not recognised.']);
        }

        $assignment = $this->assignment;

        if ($action === 'comment' && ! self::canView($actor, $assignment)) {
            abort(403, 'You may not comment on that assignment.');
        }

        if (in_array($action, self::SUPERVISOR_ACTIONS, true) && ! self::canSupervise($actor, $assignment)) {
            abort(403, 'Only the supervising officer may perform that action.');
        }

        if (in_array($action, self::ASSIGNEE_ACTIONS, true) && $actor->id !== $assignment->assigned_to && ! self::canSupervise($actor, $assignment)) {
            abort(403, 'Only the assigned officer may perform that action.');
        }

        $submittedVersion = $data['concurrency_version'] ?? null;

        return DB::transaction(function () use ($assignment, $actor, $action, $data, $submittedVersion): WorkAssignment {
            $locked = WorkAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if ($submittedVersion !== null && (int) $submittedVersion !== $locked->concurrency_version) {
                throw ValidationException::withMessages([
                    'concurrency_version' => 'This assignment changed while you were working. Refresh and try again.',
                ]);
            }

            $from = $locked->status;
            $updates = [];

            switch ($action) {
                case 'acknowledge':
                    $this->ensureStatus($locked, [WorkAssignment::STATUS_ASSIGNED]);
                    $updates = ['status' => WorkAssignment::STATUS_ACKNOWLEDGED, 'acknowledged_at' => now()];
                    break;

                case 'start':
                    $this->ensureStatus($locked, [
                        WorkAssignment::STATUS_ASSIGNED,
                        WorkAssignment::STATUS_ACKNOWLEDGED,
                        WorkAssignment::STATUS_RETURNED,
                        WorkAssignment::STATUS_ON_HOLD,
                    ]);
                    $updates = ['status' => WorkAssignment::STATUS_IN_PROGRESS];
                    break;

                case 'resume':
                    $this->ensureStatus($locked, [WorkAssignment::STATUS_ON_HOLD]);
                    $updates = ['status' => WorkAssignment::STATUS_IN_PROGRESS];
                    break;

                case 'hold':
                    $this->ensureStatus($locked, [
                        WorkAssignment::STATUS_ASSIGNED,
                        WorkAssignment::STATUS_ACKNOWLEDGED,
                        WorkAssignment::STATUS_IN_PROGRESS,
                    ]);
                    $this->requireComment($data, 'A reason is required to place a duty on hold.');
                    $updates = ['status' => WorkAssignment::STATUS_ON_HOLD];
                    break;

                case 'submit':
                    $this->ensureStatus($locked, [
                        WorkAssignment::STATUS_ACKNOWLEDGED,
                        WorkAssignment::STATUS_IN_PROGRESS,
                        WorkAssignment::STATUS_RETURNED,
                    ]);
                    $updates = [
                        'status' => $locked->status === WorkAssignment::STATUS_RETURNED
                            ? WorkAssignment::STATUS_RESUBMITTED
                            : WorkAssignment::STATUS_SUBMITTED,
                        'submitted_at' => now(),
                    ];
                    break;

                case 'return':
                    $this->ensureStatus($locked, [
                        WorkAssignment::STATUS_SUBMITTED,
                        WorkAssignment::STATUS_RESUBMITTED,
                    ]);
                    $this->requireComment($data, 'Explain what needs correcting before the work is returned.');
                    $updates = [
                        'status' => WorkAssignment::STATUS_RETURNED,
                        'last_returned_at' => now(),
                    ];
                    break;

                case 'complete':
                    $this->ensureStatus($locked, [
                        WorkAssignment::STATUS_SUBMITTED,
                        WorkAssignment::STATUS_RESUBMITTED,
                    ]);
                    $updates = ['status' => WorkAssignment::STATUS_COMPLETED, 'completed_at' => now()];
                    break;

                case 'cancel':
                    if (! $locked->isOpen()) {
                        throw ValidationException::withMessages(['action' => 'This duty is already closed.']);
                    }
                    $this->requireComment($data, 'A reason is required to cancel a duty.');
                    $updates = ['status' => WorkAssignment::STATUS_CANCELLED, 'cancelled_at' => now()];
                    break;

                case 'extend':
                    if (! $locked->isOpen()) {
                        throw ValidationException::withMessages(['action' => 'A closed duty cannot be extended.']);
                    }
                    if (! isset($data['due_at']) || $data['due_at'] === '') {
                        throw ValidationException::withMessages(['due_at' => 'Choose a new deadline.']);
                    }
                    $this->requireComment($data, 'Record why the deadline is changing.');
                    $updates = ['due_at' => $data['due_at']];
                    break;

                case 'reassign':
                    if (! $locked->isOpen()) {
                        throw ValidationException::withMessages(['action' => 'A closed duty cannot be reassigned.']);
                    }
                    $target = User::query()->whereKey($data['assigned_to'] ?? null)->where('status', 'active')->first();

                    if ($target === null || $target->id === $locked->assigned_to) {
                        throw ValidationException::withMessages(['assigned_to' => 'Choose a different active officer.']);
                    }

                    $updates = ['assigned_to' => $target->id, 'status' => WorkAssignment::STATUS_ASSIGNED];
                    break;

                case 'comment':
                    if (! $locked->isOpen()) {
                        throw ValidationException::withMessages(['action' => 'A closed duty cannot receive new comments.']);
                    }
                    $this->requireComment($data, 'Write the comment before saving.');
                    break;

                default:
                    throw ValidationException::withMessages(['action' => 'That action is not recognised.']);
            }

            $updates['concurrency_version'] = $locked->concurrency_version + 1;
            $locked->update($updates);

            $comment = isset($data['comments']) ? trim((string) $data['comments']) : null;

            if ($comment !== null && $comment !== '') {
                $locked->comments()->create([
                    'author_id' => $actor->id,
                    'kind' => $action === 'return' ? 'correction' : 'comment',
                    'body' => $comment,
                ]);
            }

            AssignmentEvent::query()->create([
                'work_assignment_id' => $locked->id,
                'actor_id' => $actor->id,
                'action' => $action,
                'from_status' => $from,
                'to_status' => $locked->status,
                'comments' => $comment,
                'created_at' => now(),
            ]);

            AuditLog::record('assignment.'.$action, $locked, [
                'reference_number' => $locked->reference_number,
                'from_status' => $from,
                'to_status' => $locked->status,
                'assignee_id' => $locked->assigned_to,
            ]);

            return $locked->fresh(['assignee', 'assigner']);
        });
    }

    /**
     * Notify the people affected by a transition. Called after the transaction
     * commits so a notification failure never rolls back the authoritative row.
     */
    public function notify(WorkAssignment $assignment, string $action): void
    {
        if ($action === 'comment') {
            return;
        }

        $assignee = $assignment->assignee;

        if ($assignee === null) {
            return;
        }

        [$title, $message] = match ($action) {
            'submit' => ['Duty submitted for review', 'A duty you supervise has been submitted for review.'],
            'return' => ['Duty returned for correction', 'A duty assigned to you was returned. Open it to read the correction.'],
            'complete' => ['Duty completed', 'A duty assigned to you has been reviewed and closed.'],
            'cancel' => ['Duty cancelled', 'A duty assigned to you has been cancelled.'],
            'reassign' => ['Duty reassigned', 'A duty has been assigned to you.'],
            'extend' => ['Duty deadline changed', 'The deadline on one of your duties has changed.'],
            default => ['Duty updated', 'A duty assigned to you has been updated.'],
        };

        $recipient = in_array($action, ['submit'], true) ? $assignment->assigner : $assignee;

        if ($recipient !== null) {
            PortalNotifier::send(
                $recipient,
                $title,
                $message,
                route('assignments.show', $assignment),
                'assignment',
            );
        }

        if ($action === 'reassign') {
            PortalNotifier::send(
                $assignee,
                'Duty assigned to you',
                'A new duty has been assigned to you with reference '.$assignment->reference_number.'.',
                route('assignments.show', $assignment),
                'assignment',
            );
        }
    }

    /**
     * @param  list<string>  $allowed
     */
    private function ensureStatus(WorkAssignment $assignment, array $allowed): void
    {
        if (! in_array($assignment->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'action' => 'That action is not available while the duty is '.$assignment->statusLabel().'.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requireComment(array $data, string $message): void
    {
        if (trim((string) ($data['comments'] ?? '')) === '') {
            throw ValidationException::withMessages(['comments' => $message]);
        }
    }
}
