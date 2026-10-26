<?php

namespace App\Providers;

use App\DutyCatalog;
use App\Models\OfficialLetter;
use App\Models\Payslip;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Authorisation is enforced by named duties ("desks") rather than role-name
     * comparisons, so two officers within the same department can hold genuinely
     * different responsibilities (PRD §2.1; Master Prompt §E).
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        $active = static fn (User $user): bool => $user->status === 'active';

        Gate::define('review-profile-change-requests', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::REGISTRY_PROFILE_CHANGE_REVIEW));

        Gate::define('any-registry-approval', fn (User $user): bool => $active($user) && $user->hasDuty(
            DutyCatalog::REGISTRY_PROFILE_CHANGE_REVIEW,
            DutyCatalog::REGISTRY_HEAD_REVIEW,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
            DutyCatalog::REGISTRY_DIRECTOR_ESCALATION,
        ));

        // Registry chain of command (OD-20): officer → Head of HR → Registrar,
        // with reserved matters escalated to the Director.
        Gate::define('head-review-registry', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::REGISTRY_HEAD_REVIEW));

        Gate::define('approve-as-registrar', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::REGISTRY_REGISTRAR_APPROVE));

        Gate::define('escalate-to-director', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::REGISTRY_DIRECTOR_ESCALATION));

        Gate::define('lead-registry-unit', fn (User $user): bool => $active($user) && $user->hasDuty(
            DutyCatalog::DUTY_LEAD,
            ...DutyCatalog::DELEGATED_LEAD_DUTIES,
        ) && $user->primaryUnit() === 'registry');

        // Read access to the personnel directory and full staff profiles is
        // distinct from the power to create or correct records. Registry
        // leaders need the full record without inheriting account-wide or
        // technical-administration authority.
        Gate::define('view-personnel-records', fn (User $user): bool => $active($user) && $user->hasDuty(
            DutyCatalog::REGISTRY_PERSONNEL_RECORDS,
            DutyCatalog::REGISTRY_HEAD_REVIEW,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
            DutyCatalog::REGISTRY_DIRECTOR_ESCALATION,
        ));

        Gate::define('manage-personnel-records', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::REGISTRY_PERSONNEL_RECORDS));

        Gate::define('manage-memos', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::MEMO_AUTHOR));

        Gate::define('manage-payslips', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::BURSARY_PAYSLIP_UPLOAD, DutyCatalog::BURSARY_PAYSLIP_RELEASE));

        Gate::define('release-payslips', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::BURSARY_PAYSLIP_RELEASE));

        Gate::define('manage-duties', fn (User $user, User $target): bool => $target->canManageDutiesOf($user));

        Gate::define('view-payslip', function (User $user, Payslip $payslip): bool {
            return $user->status === 'active'
                && $payslip->status === Payslip::STATUS_PUBLISHED
                && $payslip->user_id === $user->id;
        });

        Gate::define('access-leave-approvals', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::LEAVE_REVIEW, DutyCatalog::LEAVE_VERIFY, DutyCatalog::LEAVE_DECIDE));

        Gate::define('view-official-letter', function (User $user, OfficialLetter $letter): bool {
            return $user->status === 'active' && (
                $letter->user_id === $user->id ||
                $user->hasDuty(DutyCatalog::REGISTRY_LETTERS_ATTESTATION, DutyCatalog::LETTER_ISSUE)
            );
        });

        Gate::define('manage-registry-requests', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::REGISTRY_SERVICE_DESK));

        // Registry correspondence and assigned work (OD-20).
        Gate::define('manage-correspondence', fn (User $user): bool => $active($user) && $user->hasDuty(
            DutyCatalog::REGISTRY_PERSONNEL_RECORDS,
            DutyCatalog::REGISTRY_LETTERS_ATTESTATION,
            DutyCatalog::REGISTRY_HEAD_REVIEW,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
            DutyCatalog::LETTER_ISSUE,
            DutyCatalog::MEMO_AUTHOR,
        ));

        Gate::define('issue-correspondence', fn (User $user): bool => $active($user) && $user->hasDuty(
            DutyCatalog::REGISTRY_LETTERS_ATTESTATION,
            DutyCatalog::LETTER_ISSUE,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
        ));

        Gate::define('manage-organisation', fn (User $user): bool => $active($user) && $user->hasDuty(
            DutyCatalog::ACCOUNT_MANAGE,
            DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
        ));

        Gate::define('view-assignments', fn (User $user): bool => $active($user));

        Gate::define('assign-duties', fn (User $user): bool => $active($user));

        Gate::define('manage-bursary-requests', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::BURSARY_SERVICE_DESK));

        Gate::define('manage-ict-requests', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::ICT_SERVICE_DESK));

        Gate::define('view-service-request', function (User $user, ServiceRequest $request): bool {
            return $request->canAccess($user);
        });

        Gate::define('view-reports', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::REPORTS_VIEW));

        Gate::define('manage-accounts', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::ACCOUNT_MANAGE));

        Gate::define('view-audit-log', fn (User $user): bool => $active($user) && $user->hasDuty(DutyCatalog::AUDIT_VIEW));

        RateLimiter::for('login', function (Request $request): Limit {
            $identifier = $request->input('identifier');
            $key = is_string($identifier)
                ? Str::lower(Str::transliterate(trim($identifier)))
                : 'unknown';

            return Limit::perMinute(5)->by($key.'|'.$request->ip());
        });
    }
}
