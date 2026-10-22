<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\DutyCatalog;
use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'staff_number', 'status', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** @return HasOne<StaffProfile, $this> */
    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    /** @return HasOne<StaffProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    /** @return HasMany<ProfileChangeRequest, $this> */
    public function profileChangeRequests(): HasMany
    {
        return $this->hasMany(ProfileChangeRequest::class);
    }

    /** @return HasMany<Memo, $this> */
    public function authoredMemos(): HasMany
    {
        return $this->hasMany(Memo::class, 'author_id');
    }

    /** @return HasMany<MemoRecipient, $this> */
    public function memoRecipients(): HasMany
    {
        return $this->hasMany(MemoRecipient::class);
    }

    /** @return HasMany<Payslip, $this> */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /** @return HasMany<LeaveApplication, $this> */
    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    /** @return HasMany<LeaveEntitlement, $this> */
    public function leaveEntitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }

    /** @return HasMany<OfficialLetter, $this> */
    public function officialLetters(): HasMany
    {
        return $this->hasMany(OfficialLetter::class);
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function assignedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_to_id');
    }

    /** @return HasMany<ReportExportAudit, $this> */
    public function reportExportAudits(): HasMany
    {
        return $this->hasMany(ReportExportAudit::class, 'actor_id');
    }

    /** @return HasMany<WorkAssignment, $this> */
    public function assignmentsGiven(): HasMany
    {
        return $this->hasMany(WorkAssignment::class, 'assigned_by');
    }

    /** @return HasMany<WorkAssignment, $this> */
    public function assignmentsReceived(): HasMany
    {
        return $this->hasMany(WorkAssignment::class, 'assigned_to');
    }

    /**
     * Reporting lines where this user is the subordinate.
     *
     * @return HasMany<ReportingRelationship, $this>
     */
    public function reportingLines(): HasMany
    {
        return $this->hasMany(ReportingRelationship::class, 'subordinate_id');
    }

    /**
     * Reporting lines where this user is the supervisor.
     *
     * @return HasMany<ReportingRelationship, $this>
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(ReportingRelationship::class, 'supervisor_id');
    }

    /** @return HasMany<Delegation, $this> */
    public function delegationsReceived(): HasMany
    {
        return $this->hasMany(Delegation::class, 'delegate_id');
    }

    /**
     * The officer this user reports to today, if a line is configured.
     */
    public function currentSupervisor(): ?User
    {
        $relationship = $this->reportingLines()
            ->with('supervisor')
            ->effectiveOn()
            ->orderByDesc('is_primary')
            ->orderByDesc('effective_from')
            ->first();

        return $relationship?->supervisor;
    }

    /**
     * Whether this user currently supervises the given officer through a dated
     * reporting line (OD-20: hierarchy is data, not role names).
     */
    public function isSupervisorOf(User $subordinate): bool
    {
        if ($this->id === $subordinate->id) {
            return false;
        }

        return $subordinate->reportingLines()
            ->effectiveOn()
            ->where('supervisor_id', $this->id)
            ->exists();
    }

    /**
     * Whether a time-bound delegation grants this user the given duty scope today.
     */
    public function hasActiveDelegationFor(string $scope): bool
    {
        return $this->delegationsReceived()
            ->active()
            ->where(function ($query) use ($scope): void {
                $query->where('scope', $scope)->orWhere('scope', '*');
            })
            ->exists();
    }

    /**
     * Duties whose current holders are reported for separation-of-duties review.
     *
     * Separation of duties is never enforced by silently stripping a baseline
     * duty (that would break existing accounts); it is surfaced at assignment
     * time and verified per action.
     *
     * @var array<string, string>
     */
    public const SEPARATION_OF_DUTIES = [
        DutyCatalog::BURSARY_PAYSLIP_RELEASE => DutyCatalog::BURSARY_PAYSLIP_UPLOAD,
    ];

    /**
     * Separation-of-duties conflicts this user currently holds.
     *
     * @return list<string>
     */
    public function separationOfDutyConflicts(): array
    {
        $held = $this->dutyCodes();

        $conflicts = [];

        foreach (self::SEPARATION_OF_DUTIES as $duty => $incompatibleWith) {
            if (in_array($duty, $held, true) && in_array($incompatibleWith, $held, true)) {
                $conflicts[] = $duty.' + '.$incompatibleWith;
            }
        }

        return $conflicts;
    }

    /**
     * Whether assigning (or the account already holding) this duty would break
     * a separation-of-duties rule.
     */
    public function wouldBreachSeparationOfDuties(string $code): bool
    {
        $incompatible = self::SEPARATION_OF_DUTIES[$code] ?? null;

        return $incompatible !== null && $this->hasDuty($incompatible);
    }

    /**
     * Whether the given user may assign/withdraw duties for this account.
     *
     * ICT and Institutional superadmins (`account.manage`) may manage any
     * account. A duty lead (OD-19) may additionally manage assignments for
     * colleagues in their own department, but never for their own account, and
     * never to grant an account-wide administrative duty.
     *
     * Where the lead's role maps to a service unit (Registry, Bursary, ICT),
     * the target must belong to that same unit as well; this prevents, for
     * example, a Registry lead from reaching into Bursary.
     */
    public function canManageDutiesOf(User $actor): bool
    {
        if ($actor->id === $this->id || $actor->status !== 'active') {
            return false;
        }

        if ($this->canAssignAdministrativeDuties($actor)) {
            return true;
        }

        if (! $actor->hasDuty(DutyCatalog::DUTY_LEAD, ...DutyCatalog::DELEGATED_LEAD_DUTIES)) {
            return false;
        }

        $actorDepartment = $actor->profile?->department_id;

        if ($actorDepartment === null || $actorDepartment !== $this->profile?->department_id) {
            return false;
        }

        $unit = $actor->primaryUnit();

        // A generic departmental lead (no service-unit role) manages by
        // department alone; a unit lead must also match the unit.
        return $unit === null || $this->primaryUnit() === $unit;
    }

    /**
     * Only ICT / Institutional superadmins may grant account-wide admin duties.
     */
    public function canAssignAdministrativeDuties(User $actor): bool
    {
        return $actor->status === 'active' && $actor->hasDuty(DutyCatalog::ACCOUNT_MANAGE);
    }

    /**
     * The unit a user primarily belongs to, derived from their role first and
     * their department code as a fallback.
     */
    public function primaryUnit(): ?string
    {
        $byRole = match ($this->role) {
            UserRole::HrRegistryAdmin, UserRole::InstitutionalSuperadmin => 'registry',
            UserRole::BursaryAdmin => 'bursary',
            UserRole::IctSuperadmin => 'ict',
            default => null,
        };

        if ($byRole !== null) {
            return $byRole;
        }

        return match (strtoupper((string) $this->profile?->department?->code)) {
            'REG', 'REGISTRY' => 'registry',
            'BUR', 'BURSARY' => 'bursary',
            'ICT' => 'ict',
            default => null,
        };
    }

    /**
     * Whether this user may assign or withdraw duties for colleagues in their
     * unit, either as a generic lead or through a delegated Registry office.
     */
    public function isDutyLead(): bool
    {
        return $this->hasDuty(DutyCatalog::DUTY_LEAD, ...DutyCatalog::DELEGATED_LEAD_DUTIES);
    }

    /**
     * The Registry chain-of-command positions this user holds, most senior
     * first (OD-20). Used to route Registry work and to label dashboards.
     *
     * @return list<string>
     */
    public function registryPositions(): array
    {
        $positions = [];

        if ($this->hasDuty(DutyCatalog::REGISTRY_DIRECTOR_ESCALATION)) {
            $positions[] = DutyCatalog::REGISTRY_DIRECTOR_ESCALATION;
        }

        if ($this->hasDuty(DutyCatalog::REGISTRY_REGISTRAR_APPROVE)) {
            $positions[] = DutyCatalog::REGISTRY_REGISTRAR_APPROVE;
        }

        if ($this->hasDuty(DutyCatalog::REGISTRY_HEAD_REVIEW)) {
            $positions[] = DutyCatalog::REGISTRY_HEAD_REVIEW;
        }

        return $positions;
    }

    /**
     * The most senior Registry office held, or null for an ordinary Registry
     * officer. Drives headings and scoping on the Registry dashboards.
     */
    public function registryOffice(): ?string
    {
        return $this->registryPositions()[0] ?? null;
    }

    /** @return BelongsToMany<Duty, $this> */
    public function duties(): BelongsToMany
    {
        return $this->belongsToMany(Duty::class)
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    /**
     * Whether this user currently holds any of the given duty codes.
     */
    public function hasDuty(string ...$codes): bool
    {
        if ($codes === []) {
            return false;
        }

        $this->loadMissing('duties');

        return $this->duties->whereIn('code', $codes)->isNotEmpty();
    }

    /**
     * @return list<string>
     */
    public function dutyCodes(): array
    {
        $this->loadMissing('duties');

        return $this->duties->pluck('code')->values()->all();
    }

    /**
     * Reset this user's duties to the baseline set implied by their current
     * role. Called when an account is created and whenever its role changes.
     */
    public function syncRoleBaselineDuties(): void
    {
        $dutyIds = collect(DutyCatalog::baselineFor($this->role))
            ->map(fn (string $code): int => DutyCatalog::ensureDuty($code)->getKey())
            ->all();

        $this->duties()->sync($dutyIds);
        $this->unsetRelation('duties');
    }
}
