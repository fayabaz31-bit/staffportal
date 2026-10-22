<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delegator_id',
    'delegate_id',
    'organisational_unit_id',
    'scope',
    'starts_at',
    'ends_at',
    'reason',
    'revoked_at',
    'revoked_by',
    'created_by',
])]
class Delegation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    /** @return BelongsTo<User, $this> */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    /** @return BelongsTo<OrganisationalUnit, $this> */
    public function organisationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganisationalUnit::class);
    }

    /** @param  Builder<Delegation>  $query */
    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && $this->starts_at->isPast()
            && $this->ends_at->isFuture();
    }
}
