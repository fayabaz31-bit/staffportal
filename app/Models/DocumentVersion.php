<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['correspondence_document_id', 'version', 'body_html', 'created_by', 'created_at'])]
class DocumentVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CorrespondenceDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceDocument::class, 'correspondence_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
