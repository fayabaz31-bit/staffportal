<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'leave_application_id',
    'reference_number',
    'document_type',
    'subject',
    'body_html',
    'storage_path',
    'file_hash',
    'file_size',
    'issued_by',
    'issued_at',
])]
class OfficialLetter extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return BelongsTo<LeaveApplication, $this> */
    public function leaveApplication(): BelongsTo
    {
        return $this->belongsTo(LeaveApplication::class);
    }
}
