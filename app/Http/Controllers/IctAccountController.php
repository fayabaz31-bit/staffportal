<?php

namespace App\Http\Controllers;

use App\DutyCatalog;
use App\Models\AuditLog;
use App\Models\Duty;
use App\Models\User;
use App\Notifications\PortalNotifier;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IctAccountController extends Controller
{
    /**
     * Account operations console for ICT (PRD §2.1, §E, AUTH/AC-10/AC-15).
     *
     * Deliberately exposes account, role and status metadata only -- never
     * private documents or HR/Bursary transaction content.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-accounts');

        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $role = $request->string('role')->toString();

        $users = User::query()
            ->with(['staffProfile.department', 'duties'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('staff_number', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->when(in_array($status, ['active', 'suspended', 'deactivated'], true), fn (Builder $query) => $query->where('status', $status))
            ->when(UserRole::tryFrom($role) !== null, fn (Builder $query) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('ict.accounts', [
            'users' => $users,
            'search' => $search,
            'statusFilter' => $status,
            'roleFilter' => $role,
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Activate, suspend or deactivate an account without destroying history.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-accounts');

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'deactivated'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($user->id === $request->user()->id && $validated['status'] !== 'active') {
            return back()->with('error', 'You cannot suspend or deactivate your own account.');
        }

        DB::transaction(function () use ($user, $validated, $request): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $previous = $locked->status;

            $locked->status = $validated['status'];
            $locked->save();

            AuditLog::record('account.status_changed', $locked, [
                'previous_status' => $previous,
                'new_status' => $validated['status'],
                'reason' => $validated['reason'] ?? null,
            ], $request->user()->id);
        });

        if ($validated['status'] === 'active') {
            PortalNotifier::send(
                $user,
                'Account activated',
                'Your portal account has been activated. You can now sign in and use your staff services.',
                route('dashboard'),
                'account',
            );
        }

        return back()->with('status', 'Account status updated to '.$validated['status'].'.');
    }

    /**
     * Change a role assignment; effective immediately and recorded (AC-10).
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-accounts');

        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $newRole = UserRole::from($validated['role']);
        $actor = $request->user();

        if ($user->id === $actor->id && $newRole !== $actor->role) {
            return back()->with('error', 'You cannot change your own role assignment.');
        }

        if ($newRole === UserRole::InstitutionalSuperadmin && $actor->role !== UserRole::InstitutionalSuperadmin) {
            return back()->with('error', 'Only an Institutional Superadmin may assign the Institutional Superadmin role.');
        }

        if ($user->role === $newRole) {
            return back()->with('status', 'No change: this account already holds that role.');
        }

        DB::transaction(function () use ($user, $newRole, $request): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $previous = $locked->role;

            $locked->role = $newRole;
            $locked->save();

            AuditLog::record('account.role_changed', $locked, [
                'previous_role' => $previous->value,
                'new_role' => $newRole->value,
            ], $request->user()->id);
        });

        PortalNotifier::send(
            $user,
            'Role assignment updated',
            'Your portal role has changed. Sign in again if your dashboard does not refresh.',
            route('dashboard'),
            'account',
        );

        return back()->with('status', 'Role assignment updated and recorded in the audit trail.');
    }

    /**
     * Issue a one-time temporary password so ICT can provision access.
     */
    public function issueTemporaryPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-accounts');

        $temporaryPassword = Str::password(16);

        DB::transaction(function () use ($user, $temporaryPassword, $request): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $locked->password = $temporaryPassword;
            $locked->save();

            AuditLog::record('account.temporary_password_issued', $locked, [], $request->user()->id);
        });

        return back()
            ->with('status', 'A temporary password has been issued for '.$user->name.'. It is shown once below; it is never stored in plain text.')
            ->with('temporaryPassword', $temporaryPassword);
    }

    /**
     * Duty assignment landing page for the officer's own team.
     *
     * This is the discoverable entry point for unit leads (OD-19): a head of
     * department opens "Team duties", sees colleagues they may manage, and
     * assigns duties without needing the ICT console.
     */
    public function teamDuties(Request $request): View
    {
        $actor = $request->user();

        abort_unless(
            $actor->isDutyLead() || $actor->hasDuty(DutyCatalog::ACCOUNT_MANAGE),
            403,
        );

        $managesInstitutionWide = $actor->canAssignAdministrativeDuties($actor);

        $team = User::query()
            ->with(['staffProfile.department', 'duties'])
            ->whereKeyNot($actor->getKey())
            ->when(! $managesInstitutionWide, function (Builder $query) use ($actor): void {
                $unit = $actor->primaryUnit();
                $departmentId = $actor->profile?->department_id;

                if ($unit === null || $departmentId === null) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereHas('staffProfile', fn (Builder $profile) => $profile->where('department_id', $departmentId));
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (User $candidate): bool => $candidate->canManageDutiesOf($actor))
            ->values();

        return view('ict.team-duties', [
            'team' => $team,
            'managesInstitutionWide' => $managesInstitutionWide,
            'unit' => $actor->primaryUnit(),
        ]);
    }

    /**
     * Duty assignment page for a single account.
     *
     * Reachable by ICT / Institutional superadmins and by unit leads for
     * colleagues in their own department and unit (OD-19).
     */
    public function duties(Request $request, User $user): View
    {
        Gate::authorize('manage-duties', $user);

        $actor = $request->user();
        $mayAssignAdministrative = $user->canAssignAdministrativeDuties($actor);

        DutyCatalog::sync();

        $assignable = Duty::query()
            ->where('is_active', true)
            ->when(! $mayAssignAdministrative, fn (Builder $query) => $query->whereNotIn('code', DutyCatalog::ADMINISTRATIVE_DUTIES))
            ->orderBy('unit')
            ->orderBy('label')
            ->get()
            ->groupBy('unit');

        return view('ict.duties', [
            'account' => $user->load(['staffProfile.department', 'duties']),
            'assignable' => $assignable,
            'mayAssignAdministrative' => $mayAssignAdministrative,
        ]);
    }

    /**
     * Grant a specific duty ("desk") to an account; audited and notified.
     *
     * This is how two officers in the same department are given genuinely
     * different responsibilities, independent of their broad role.
     */
    public function grantDuty(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-duties', $user);

        $validated = $request->validate([
            'duty' => ['required', 'string', Rule::in(array_keys(DutyCatalog::all()))],
        ]);

        $duty = DutyCatalog::ensureDuty($validated['duty']);

        // A unit lead may not grant account-wide administrative duties.
        if (in_array($duty->code, DutyCatalog::ADMINISTRATIVE_DUTIES, true)
            && ! $user->canAssignAdministrativeDuties($request->user())) {
            return back()->with('error', 'Only ICT or an Institutional Superadmin may grant administrative duties.');
        }

        if ($user->duties()->whereKey($duty->id)->exists()) {
            return back()->with('status', 'No change: '.$user->name.' already holds this duty.');
        }

        // Separation of duties: release and upload must not be held by one officer.
        if ($user->wouldBreachSeparationOfDuties($duty->code)) {
            return back()->with('error', 'Separation of duties: '.$duty->label.' cannot be held by the same officer who uploads payslip batches. Assign a different officer.');
        }

        $user->duties()->attach($duty->id, [
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
        ]);
        $user->unsetRelation('duties');

        AuditLog::record('account.duty_granted', $user, ['duty' => $duty->code], $request->user()->id);

        PortalNotifier::send(
            $user,
            'New responsibility assigned',
            'A new portal responsibility ('.$duty->label.') has been assigned to your account.',
            route('dashboard'),
            'account',
        );

        return back()->with('status', 'Duty assigned and recorded in the audit trail.');
    }

    /**
     * Withdraw a duty from an account; audited and notified.
     */
    public function revokeDuty(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-duties', $user);

        $validated = $request->validate([
            'duty' => ['required', 'string', Rule::in(array_keys(DutyCatalog::all()))],
        ]);

        $duty = DutyCatalog::ensureDuty($validated['duty']);

        // A unit lead may not pull account-wide administrative duties.
        if (in_array($duty->code, DutyCatalog::ADMINISTRATIVE_DUTIES, true)
            && ! $user->canAssignAdministrativeDuties($request->user())) {
            return back()->with('error', 'Only ICT or an Institutional Superadmin may withdraw administrative duties.');
        }

        if (! $user->duties()->whereKey($duty->id)->exists()) {
            return back()->with('status', 'No change: this account does not hold that duty.');
        }

        $user->duties()->detach($duty->id);
        $user->unsetRelation('duties');

        AuditLog::record('account.duty_revoked', $user, ['duty' => $duty->code], $request->user()->id);

        PortalNotifier::send(
            $user,
            'Responsibility withdrawn',
            'A portal responsibility ('.$duty->label.') has been withdrawn from your account.',
            route('dashboard'),
            'account',
        );

        return back()->with('status', 'Duty withdrawn and recorded in the audit trail.');
    }
}
