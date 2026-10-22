<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payslip_batch_id',
    'user_id',
    'staff_number',
    'payroll_period',
    'original_filename',
    'storage_path',
    'file_hash',
    'file_size',
    'status',
    'published_at',
    'withdrawn_at',
    'withdrawn_by',
    'withdrawal_reason',
    'download_count',
    'last_downloaded_at',
])]
class Payslip extends Model
{
    use HasFactory;

    public const string STATUS_PUBLISHED = 'published';

    public const string STATUS_WITHDRAWN = 'withdrawn';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'last_downloaded_at' => 'datetime',
            'file_size' => 'integer',
            'download_count' => 'integer',
        ];
    }

    /** @return BelongsTo<PayslipBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayslipBatch::class, 'payslip_batch_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function formattedPeriod(): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->payroll_period)->translatedFormat('F Y');
        } catch (\Throwable) {
            return $this->payroll_period;
        }
    }

    public function formattedFileSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' KB';
        }

        return $bytes.' B';
    }
}
