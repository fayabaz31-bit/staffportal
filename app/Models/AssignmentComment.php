<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['work_assignment_id', 'author_id', 'kind', 'body'])]
class AssignmentComment extends Model
{
    /** @return BelongsTo<WorkAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(WorkAssignment::class, 'work_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
