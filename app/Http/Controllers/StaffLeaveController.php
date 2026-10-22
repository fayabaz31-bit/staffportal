<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\LeaveApprovalAction;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\PortalNotifier;
use App\UserRole;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffLeaveController extends Controller
{
    /**
     * Display staff leave dashboard with balances, applications, and policy notices.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $currentYear = (int) Carbon::now()->year;

        $entitlements = LeaveEntitlement::with('leaveType')
            ->where('user_id', $user->id)
            ->where('year', $currentYear)
            ->get();

        $hasUnconfiguredPolicies = $entitlements->contains(fn (LeaveEntitlement $e): bool => ! $e->is_policy_configured);

        $applications = LeaveApplication::with(['leaveType', 'approver', 'letter'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $availableLeaveTypes = LeaveType::where('is_active', true)->get();

        return view('leave.index', [
            'user' => $user,
            'currentYear' => $currentYear,
            'entitlements' => $entitlements,
            'hasUnconfiguredPolicies' => $hasUnconfiguredPolicies,
            'applications' => $applications,
            'availableLeaveTypes' => $availableLeaveTypes,
        ]);
    }

    /**
     * Submit a new leave application with validation, balance check, and overlap protection.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $leaveType = LeaveType::where('id', $validated['leave_type_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if ($leaveType->requires_attachment && ! $request->hasFile('attachment')) {
            return back()->withInput()->withErrors([
                'attachment' => 'A supporting document (medical certificate or evidence) is required for '.$leaveType->name.'.',
            ]);
        }

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->startOfDay();

        // Calculate business working days (Monday - Friday)
        $businessDays = 0;
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            if (! $date->isWeekend()) {
                $businessDays++;
            }
        }

        if ($businessDays === 0) {
            return back()->withInput()->withErrors([
                'start_date' => 'The selected date range contains no business working days (weekends are excluded).',
            ]);
        }

        // Prevent overlapping active/approved leave applications
        $overlapping = LeaveApplication::where('user_id', $user->id)
            ->whereIn('status', [
                LeaveApplication::STATUS_SUBMITTED,
                LeaveApplication::STATUS_PENDING_SUPERVISOR,
                LeaveApplication::STATUS_PENDING_HR,
                LeaveApplication::STATUS_PENDING_DIRECTOR,
                LeaveApplication::STATUS_APPROVED,
            ])
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->where('start_date', '<=', $endDate->toDateString())
                    ->where('end_date', '>=', $startDate->toDateString());
            })
            ->exists();

        if ($overlapping) {
            return back()->withInput()->withErrors([
                'start_date' => 'You already have an active or approved leave application covering all or part of these dates.',
            ]);
        }

        // Check entitlement balance
        $year = (int) $startDate->year;
        $entitlement = LeaveEntitlement::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if (! $entitlement || $entitlement->remainingDays() < $businessDays) {
            $available = $entitlement ? $entitlement->remainingDays() : 0;

            return back()->withInput()->withErrors([
                'leave_type_id' => "Insufficient leave entitlement balance for {$leaveType->name}. Requested: {$businessDays} day(s), Available: {$available} day(s).",
            ]);
        }

        // Store attachment in private storage if present
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'private');
        }

        // Determine initial approval stage and status based on leave type config
        if ($leaveType->requires_supervisor_review) {
            $stage = LeaveApplication::STAGE_SUPERVISOR;
            $status = LeaveApplication::STATUS_PENDING_SUPERVISOR;
        } elseif ($leaveType->requires_hr_verification) {
            $stage = LeaveApplication::STAGE_HR;
            $status = LeaveApplication::STATUS_PENDING_HR;
        } else {
            $stage = LeaveApplication::STAGE_DIRECTOR;
            $status = LeaveApplication::STATUS_PENDING_DIRECTOR;
        }

        $application = DB::transaction(function () use ($user, $leaveType, $startDate, $endDate, $businessDays, $validated, $attachmentPath, $status, $stage, $entitlement): LeaveApplication {
            // Lock entitlement to protect against race conditions
            $lockedEntitlement = LeaveEntitlement::where('id', $entitlement->id)->lockForUpdate()->firstOrFail();
            if ($lockedEntitlement->remainingDays() < $businessDays) {
                abort(422, 'Insufficient leave balance.');
            }

            $lockedEntitlement->increment('pending_days', $businessDays);

            $application = LeaveApplication::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days_requested' => $businessDays,
                'reason' => $validated['reason'],
                'attachment_path' => $attachmentPath,
                'status' => $status,
                'current_stage' => $stage,
                'assigned_department_id' => $user->profile?->department_id,
                'concurrency_version' => 1,
            ]);

            LeaveApprovalAction::create([
                'leave_application_id' => $application->id,
                'actor_id' => $user->id,
                'stage' => 'submission',
                'action' => 'submitted',
                'comments' => 'Application submitted by staff member.',
            ]);

            return $application;
        });

        PortalNotifier::sendMany(
            $this->approversForStage($stage, $user->profile?->department_id),
            'Leave application awaiting decision',
            'A new leave application has been submitted and routed to your approval queue.',
            route('approvals.leave.index'),
            'leave',
        );

        AuditLog::record('leave.submitted', $application, [
            'leave_type' => $leaveType->name,
            'days_requested' => $businessDays,
            'stage' => $stage,
        ]);

        return redirect()->route('leave.index')->with('status', "Your application for {$businessDays} day(s) of {$leaveType->name} has been submitted successfully.");
    }

    /**
     * Cancel an existing pending leave application and restore allocated balance.
     */
    public function cancel(Request $request, LeaveApplication $leave): RedirectResponse
    {
        $user = $request->user();

        if ($leave->user_id !== $user->id) {
            abort(403, 'Unauthorized access to leave application.');
        }

        if (! $leave->isPending()) {
            return back()->with('error', 'Only pending leave applications can be cancelled.');
        }

        DB::transaction(function () use ($leave, $user): void {
            $lockedLeave = LeaveApplication::where('id', $leave->id)->lockForUpdate()->firstOrFail();

            if (! $lockedLeave->isPending()) {
                abort(422, 'This application is no longer pending.');
            }

            // Restore pending balance
            $entitlement = LeaveEntitlement::where('user_id', $user->id)
                ->where('leave_type_id', $lockedLeave->leave_type_id)
                ->where('year', $lockedLeave->start_date->year)
                ->lockForUpdate()
                ->first();

            if ($entitlement) {
                $entitlement->pending_days = max(0, $entitlement->pending_days - $lockedLeave->days_requested);
                $entitlement->save();
            }

            $lockedLeave->status = LeaveApplication::STATUS_CANCELLED;
            $lockedLeave->cancelled_at = now();
            $lockedLeave->concurrency_version = $lockedLeave->concurrency_version + 1;
            $lockedLeave->save();

            LeaveApprovalAction::create([
                'leave_application_id' => $lockedLeave->id,
                'actor_id' => $user->id,
                'stage' => $lockedLeave->current_stage,
                'action' => 'cancelled',
                'comments' => 'Application cancelled by applicant.',
            ]);
        });

        AuditLog::record('leave.cancelled', $leave, [
            'days_requested' => $leave->days_requested,
        ]);

        return redirect()->route('leave.index')->with('status', 'Your leave application was cancelled and pending days were restored.');
    }

    /**
     * Resolve the active officers who must act at a given workflow stage.
     *
     * @return Collection<int, User>
     */
    private function approversForStage(string $stage, ?int $departmentId): Collection
    {
        return match ($stage) {
            LeaveApplication::STAGE_SUPERVISOR => User::query()
                ->where('status', 'active')
                ->where('role', UserRole::HeadOfDepartment)
                ->whereHas('staffProfile', fn ($query) => $query->where('department_id', $departmentId))
                ->get(),
            LeaveApplication::STAGE_HR => User::query()
                ->where('status', 'active')
                ->where('role', UserRole::HrRegistryAdmin)
                ->get(),
            default => User::query()
                ->where('status', 'active')
                ->where('role', UserRole::Director)
                ->get(),
        };
    }
}
