<?php

namespace App\Models;

use App\Support\AssignmentWorkflow;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A piece of work assigned to an officer ("duty assignment").
 *
 * Statuses are validated by {@see AssignmentWorkflow}; this model
 * only describes the data and the relationships.
 */
#[Fillable([
    'reference_number',
    'title',
    'instructions',
    'department_id',
    'organisational_unit_id',
    'assigned_by',
    'assigned_to',
    'priority',
    'status',
    'confidentiality',
    'required_deliverables',
    'related_document_type',
    'related_document_id',
    'due_at',
    'acknowledged_at',
    'submitted_at',
    'completed_at',
    'cancelled_at',
    'last_returned_at',
    'concurrency_version',
])]
class WorkAssignment extends Model
{
    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_ASSIGNED = 'assigned';

    public const string STATUS_ACKNOWLEDGED = 'acknowledged';

    public const string STATUS_IN_PROGRESS = 'in_progress';

    public const string STATUS_SUBMITTED = 'submitted';

    public const string STATUS_RETURNED = 'returned';

    public const string STATUS_RESUBMITTED = 'resubmitted';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_CANCELLED = 'cancelled';

    public const string STATUS_ON_HOLD = 'on_hold';

    public const string PRIORITY_LOW = 'low';

    public const string PRIORITY_NORMAL = 'normal';

    public const string PRIORITY_HIGH = 'high';

    public const string PRIORITY_URGENT = 'urgent';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_returned_at' => 'datetime',
            'concurrency_version' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<OrganisationalUnit, $this> */
    public function organisationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganisationalUnit::class);
    }

    /** @return HasMany<AssignmentComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(AssignmentComment::class);
    }

    /** @return HasMany<AssignmentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(AssignmentEvent::class);
    }

    /** @return HasMany<AssignmentAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class);
    }

    /** @param  Builder<WorkAssignment>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /** @param  Builder<WorkAssignment>  $query */
    public function scopeOverdue(Builder $query): void
    {
        $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at !== null && $this->due_at->isPast();
    }

    /**
     * Human-readable status, used in views and notifications.
     */
    public function statusLabel(): string
    {
        return (string) self::statusLabels()[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_SUBMITTED => 'Submitted for review',
            self::STATUS_RETURNED => 'Returned for correction',
            self::STATUS_RESUBMITTED => 'Resubmitted',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ON_HOLD => 'On hold',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }
}
