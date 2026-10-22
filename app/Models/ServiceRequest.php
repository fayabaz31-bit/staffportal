<?php

namespace App\Models;

use App\DutyCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'tracking_reference',
    'user_id',
    'unit',
    'category',
    'subject',
    'description',
    'priority',
    'status',
    'is_confidential',
    'attachment_path',
    'assigned_to_id',
    'assigned_at',
    'resolved_at',
    'closed_at',
])]
class ServiceRequest extends Model
{
    use HasFactory;

    public const string UNIT_REGISTRY = 'registry';

    public const string UNIT_BURSARY = 'bursary';

    public const string UNIT_ICT = 'ict';

    public const string STATUS_OPEN = 'open';

    public const string STATUS_IN_PROGRESS = 'in_progress';

    public const string STATUS_ESCALATED = 'escalated';

    public const string STATUS_RESOLVED = 'resolved';

    public const string STATUS_CLOSED = 'closed';

    public const string PRIORITY_LOW = 'low';

    public const string PRIORITY_STANDARD = 'standard';

    public const string PRIORITY_HIGH = 'high';

    public const string PRIORITY_URGENT = 'urgent';

    /**
     * Routing unit => the service-desk duty that authorises handling requests
     * in that unit. Institutional oversight derives the same duties from its
     * role baseline rather than a hard-coded superuser bypass.
     *
     * @var array<string, string>
     */
    private const UNIT_DUTIES = [
        self::UNIT_REGISTRY => DutyCatalog::REGISTRY_SERVICE_DESK,
        self::UNIT_BURSARY => DutyCatalog::BURSARY_SERVICE_DESK,
        self::UNIT_ICT => DutyCatalog::ICT_SERVICE_DESK,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_confidential' => 'boolean',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /** @return HasMany<ServiceRequestMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ServiceRequestMessage::class);
    }

    /** @return HasMany<ServiceRequestMessage, $this> */
    public function visibleMessagesFor(User $viewer): HasMany
    {
        if ($this->isUnitHandler($viewer)) {
            return $this->messages();
        }

        return $this->messages()->where('is_internal', false);
    }

    /** @return HasMany<ServiceRequestStatusLog, $this> */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ServiceRequestStatusLog::class);
    }

    public function isUnitHandler(User $user): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        $duty = self::UNIT_DUTIES[$this->unit] ?? null;

        return $duty !== null && $user->hasDuty($duty);
    }

    public function canAccess(User $user): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->isUnitHandler($user);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }

    /**
     * Active officers holding the service-desk duty for a routing unit.
     *
     * @return Collection<int, User>
     */
    public static function unitHandlerUsers(string $unit): Collection
    {
        $duty = self::UNIT_DUTIES[$unit] ?? null;

        if ($duty === null) {
            return collect();
        }

        return User::query()
            ->where('status', 'active')
            ->whereHas('duties', fn (Builder $query) => $query->where('code', $duty))
            ->get();
    }
}
