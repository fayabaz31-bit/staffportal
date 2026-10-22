<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'work_assignment_id',
    'uploaded_by',
    'original_name',
    'storage_path',
    'mime_type',
    'size',
    'sha256',
    'created_at',
])]
class AssignmentAttachment extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(WorkAssignment::class, 'work_assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
