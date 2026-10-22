<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'leave_type_id',
    'start_date',
    'end_date',
    'days_requested',
    'reason',
    'attachment_path',
    'status',
    'current_stage',
    'assigned_department_id',
    'assigned_approver_id',
    'rejection_reason',
    'return_reason',
    'approval_notes',
    'approved_by',
    'approved_at',
    'rejected_by',
    'rejected_at',
    'returned_by',
    'returned_at',
    'cancelled_at',
    'concurrency_version',
])]
class LeaveApplication extends Model
{
    use HasFactory;

    public const string STATUS_SUBMITTED = 'submitted';

    public const string STATUS_PENDING_SUPERVISOR = 'pending_supervisor';

    public const string STATUS_PENDING_HR = 'pending_hr';

    public const string STATUS_PENDING_DIRECTOR = 'pending_director';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_REJECTED = 'rejected';

    public const string STATUS_RETURNED = 'returned';

    public const string STATUS_CANCELLED = 'cancelled';

    public const string STAGE_SUPERVISOR = 'supervisor';

    public const string STAGE_HR = 'hr';

    public const string STAGE_DIRECTOR = 'director';

    public const string STAGE_COMPLETED = 'completed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days_requested' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'concurrency_version' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<LeaveType, $this> */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_approver_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** @return HasMany<LeaveApprovalAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(LeaveApprovalAction::class);
    }

    /** @return HasOne<OfficialLetter, $this> */
    public function letter(): HasOne
    {
        return $this->hasOne(OfficialLetter::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_SUPERVISOR,
            self::STATUS_PENDING_HR,
            self::STATUS_PENDING_DIRECTOR,
        ], true);
    }
}
