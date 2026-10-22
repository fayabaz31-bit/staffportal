<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['memo_id', 'user_id', 'delivered_at', 'viewed_at', 'acknowledged_at'])]
class MemoRecipient extends Model
{
    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'viewed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Memo, $this> */
    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
