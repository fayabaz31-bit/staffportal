<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uploaded_by',
    'original_filename',
    'status',
    'row_count',
    'valid_row_count',
    'error_row_count',
    'preview_rows',
    'committed_at',
])]
class PersonnelImportBatch extends Model
{
    public const string STATUS_PREVIEW = 'preview';

    public const string STATUS_NEEDS_REVIEW = 'needs_review';

    public const string STATUS_COMMITTED = 'committed';

    protected function casts(): array
    {
        return [
            'preview_rows' => 'array',
            'committed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
