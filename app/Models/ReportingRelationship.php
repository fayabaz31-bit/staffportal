<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'subordinate_id',
    'supervisor_id',
    'organisational_unit_id',
    'relationship_type',
    'effective_from',
    'effective_to',
    'is_primary',
    'created_by',
])]
class ReportingRelationship extends Model
{
    public const string LINE_MANAGER = 'line_manager';

    public const string ACTING = 'acting';

    public const string DELEGATED = 'delegated';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_primary' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function subordinate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subordinate_id');
    }

    /** @return BelongsTo<User, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** @return BelongsTo<OrganisationalUnit, $this> */
    public function organisationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganisationalUnit::class);
    }

    /**
     * Relationships in force on a given day.
     *
     * @param  Builder<ReportingRelationship>  $query
     */
    public function scopeEffectiveOn(Builder $query, ?string $date = null): void
    {
        $on = $date ?? now()->toDateString();

        $query->where('effective_from', '<=', $on)
            ->where(function (Builder $query) use ($on): void {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', $on);
            });
    }
}
