<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workflow_version_id',
    'sequence',
    'code',
    'name',
    'action_required',
    'approver_type',
    'approver_role',
    'approver_duty',
    'approver_user_id',
    'is_mandatory',
    'sla_hours',
])]
class WorkflowStep extends Model
{
    public const string ACTION_REVIEW = 'review';

    public const string ACTION_RECOMMEND = 'recommend';

    public const string ACTION_APPROVE = 'approve';

    public const string ACTION_ISSUE = 'issue';

    public const string APPROVER_DUTY = 'duty';

    public const string APPROVER_ROLE = 'role';

    public const string APPROVER_USER = 'user';

    public const string APPROVER_SUPERVISOR = 'supervisor';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_mandatory' => 'boolean',
            'sla_hours' => 'integer',
        ];
    }

    /** @return BelongsTo<WorkflowVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
