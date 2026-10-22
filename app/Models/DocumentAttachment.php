<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'correspondence_document_id',
    'document_version_id',
    'uploaded_by',
    'original_name',
    'storage_path',
    'mime_type',
    'size',
    'sha256',
    'created_at',
])]
class DocumentAttachment extends Model
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

    /** @return BelongsTo<CorrespondenceDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceDocument::class, 'correspondence_document_id');
    }

    /** @return BelongsTo<DocumentVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
