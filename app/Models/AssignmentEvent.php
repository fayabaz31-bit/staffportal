<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable activity record for a work assignment.
 */
#[Fillable(['work_assignment_id', 'actor_id', 'action', 'from_status', 'to_status', 'comments', 'created_at'])]
class AssignmentEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(WorkAssignment::class, 'work_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
