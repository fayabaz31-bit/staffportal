<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'leave_type_id',
    'year',
    'allocated_days',
    'used_days',
    'pending_days',
    'is_policy_configured',
])]
class LeaveEntitlement extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'allocated_days' => 'integer',
            'used_days' => 'integer',
            'pending_days' => 'integer',
            'is_policy_configured' => 'boolean',
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

    public function remainingDays(): int
    {
        return max(0, $this->allocated_days - $this->used_days - $this->pending_days);
    }
}
