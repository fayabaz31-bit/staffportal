<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'leave_application_id',
    'actor_id',
    'stage',
    'action',
    'comments',
])]
class LeaveApprovalAction extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LeaveApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
