<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'requires_registrar_approval',
    'requires_director_approval',
    'sort_order',
    'is_active',
])]
class CorrespondenceCategory extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_registrar_approval' => 'boolean',
            'requires_director_approval' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<CorrespondenceDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(CorrespondenceDocument::class);
    }

    /** @return HasMany<CorrespondenceTemplate, $this> */
    public function templates(): HasMany
    {
        return $this->hasMany(CorrespondenceTemplate::class);
    }

    /** @param  Builder<CorrespondenceCategory>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /** @param  Builder<CorrespondenceCategory>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
