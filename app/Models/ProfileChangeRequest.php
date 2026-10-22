<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'requested_by',
    'field_key',
    'current_value',
    'proposed_value',
    'reason',
    'status',
    'current_stage',
    'reviewed_by',
    'reviewed_at',
    'reviewer_note',
    'head_reviewed_by',
    'head_reviewed_at',
    'head_note',
    'registrar_approved_by',
    'registrar_approved_at',
    'escalated_by',
    'escalated_at',
    'requires_director',
])]
class ProfileChangeRequest extends Model
{
    use HasFactory;

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_REJECTED = 'rejected';

    /**
     * Registry approval stages (OD-20): officer → Head of HR → Registrar →
     * (Director when the matter is reserved for institutional sign-off).
     */
    public const string STAGE_HEAD_OF_HR = 'head_of_hr';

    public const string STAGE_REGISTRAR = 'registrar';

    public const string STAGE_DIRECTOR = 'director';

    public const string STAGE_COMPLETED = 'completed';

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'head_reviewed_at' => 'datetime',
            'registrar_approved_at' => 'datetime',
            'escalated_at' => 'datetime',
            'requires_director' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function headReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function registrarApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function escalator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }
}
