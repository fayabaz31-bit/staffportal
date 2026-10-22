<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'author_id',
    'title',
    'body',
    'classification',
    'status',
    'audience_type',
    'audience_department_id',
    'audience_user_ids',
    'requires_acknowledgement',
    'published_at',
    'withdrawn_at',
    'withdrawn_by',
    'withdrawal_reason',
])]
class Memo extends Model
{
    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_PUBLISHED = 'published';

    public const string STATUS_WITHDRAWN = 'withdrawn';

    public const string CLASSIFICATION_NORMAL = 'normal';

    protected function casts(): array
    {
        return [
            'audience_user_ids' => 'array',
            'requires_acknowledgement' => 'boolean',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<MemoRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(MemoRecipient::class);
    }
}
