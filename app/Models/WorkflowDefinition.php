<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'document_type', 'description', 'is_active'])]
class WorkflowDefinition extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<WorkflowVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class);
    }
}
