<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workflow_definition_id',
    'version',
    'is_published',
    'effective_from',
    'effective_to',
])]
class WorkflowVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_published' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /** @return BelongsTo<WorkflowDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    /** @return HasMany<WorkflowStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sequence');
    }
}
