<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workflow_definition_id',
    'workflow_version_id',
    'document_type',
    'document_id',
    'current_step_id',
    'status',
    'started_by',
    'completed_at',
])]
class WorkflowInstance extends Model
{
    public const string STATUS_IN_PROGRESS = 'in_progress';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_REJECTED = 'rejected';

    public const string STATUS_RETURNED = 'returned';

    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkflowDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    /** @return BelongsTo<WorkflowVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    /** @return BelongsTo<WorkflowStep, $this> */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    /** @return HasMany<WorkflowAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('id');
    }
}
