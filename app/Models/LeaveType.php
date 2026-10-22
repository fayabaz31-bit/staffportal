<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'description',
    'default_days_per_year',
    'requires_attachment',
    'requires_supervisor_review',
    'requires_hr_verification',
    'is_active',
])]
class LeaveType extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_days_per_year' => 'integer',
            'requires_attachment' => 'boolean',
            'requires_supervisor_review' => 'boolean',
            'requires_hr_verification' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<LeaveApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    /** @return HasMany<LeaveEntitlement, $this> */
    public function entitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }
}
