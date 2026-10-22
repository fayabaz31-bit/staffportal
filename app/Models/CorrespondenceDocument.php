<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference_number',
    'correspondence_category_id',
    'correspondence_template_id',
    'subject',
    'originator_id',
    'recipient_user_id',
    'recipient_name',
    'status',
    'current_version',
    'workflow_instance_id',
    'approved_by',
    'approved_at',
    'issued_by',
    'issued_at',
    'superseded_by',
    'withdrawn_at',
    'delivery_status',
    'storage_path',
    'file_hash',
    'file_size',
])]
class CorrespondenceDocument extends Model
{
    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_SUBMITTED = 'submitted';

    public const string STATUS_UNDER_REVIEW = 'under_review';

    public const string STATUS_RETURNED = 'returned';

    public const string STATUS_AWAITING_APPROVAL = 'awaiting_approval';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_REJECTED = 'rejected';

    public const string STATUS_ISSUED = 'issued';

    public const string STATUS_SUPERSEDED = 'superseded';

    public const string STATUS_WITHDRAWN = 'withdrawn';

    public const string DELIVERY_NOT_SENT = 'not_sent';

    public const string DELIVERY_HANDED_OVER = 'handed_over';

    public const string DELIVERY_COLLECTED = 'collected';

    public const string DELIVERY_POSTED = 'posted';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_version' => 'integer',
            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /**
     * Documents may be edited only while they have not been issued.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
        ], true);
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    /** @return BelongsTo<CorrespondenceCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceCategory::class, 'correspondence_category_id');
    }

    /** @return BelongsTo<CorrespondenceTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceTemplate::class, 'correspondence_template_id');
    }

    /** @return BelongsTo<User, $this> */
    public function originator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'originator_id');
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /** @return BelongsTo<WorkflowInstance, $this> */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    /** @return HasMany<DocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version');
    }

    /** @return HasMany<DocumentAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    public function latestVersion(): ?DocumentVersion
    {
        return $this->versions()->reorder()->orderByDesc('version')->first();
    }

    /** @param  Builder<CorrespondenceDocument>  $query */
    public function scopeIssued(Builder $query): void
    {
        $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under review',
            self::STATUS_RETURNED => 'Returned for correction',
            self::STATUS_AWAITING_APPROVAL => 'Awaiting approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_SUPERSEDED => 'Superseded',
            self::STATUS_WITHDRAWN => 'Withdrawn',
        ];
    }

    public function statusLabel(): string
    {
        return (string) self::statusLabels()[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }
}
