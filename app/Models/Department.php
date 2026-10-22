<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'sort_order', 'is_active'])]
class Department extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Departments in the institution's official display order.
     *
     * @param  Builder<Department>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /** @return HasMany<StaffProfile, $this> */
    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    /** @return HasMany<OrganisationalUnit, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(OrganisationalUnit::class);
    }
}
