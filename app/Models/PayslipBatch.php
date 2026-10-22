<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uploaded_by',
    'payroll_period',
    'original_filename',
    'status',
    'total_files',
    'matched_files',
    'error_count',
    'reconciliation_summary',
    'approved_by',
    'approved_at',
    'published_by',
    'published_at',
    'withdrawn_by',
    'withdrawn_at',
    'withdrawal_reason',
])]
class PayslipBatch extends Model
{
    use HasFactory;

    public const string STATUS_STAGED = 'staged';

    public const string STATUS_QUARANTINED = 'quarantined';

    public const string STATUS_PUBLISHED = 'published';

    public const string STATUS_WITHDRAWN = 'withdrawn';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reconciliation_summary' => 'array',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return HasMany<Payslip, $this> */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function isQuarantined(): bool
    {
        return $this->status === self::STATUS_QUARANTINED;
    }

    public function isStaged(): bool
    {
        return $this->status === self::STATUS_STAGED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isWithdrawn(): bool
    {
        return $this->status === self::STATUS_WITHDRAWN;
    }

    public function formattedPeriod(): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->payroll_period)->translatedFormat('F Y');
        } catch (\Throwable) {
            return $this->payroll_period;
        }
    }
}
