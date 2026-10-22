<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\LeaveApprovalAction;
use App\Models\LeaveEntitlement;
use App\Models\OfficialLetter;
use App\Models\ReferenceSequence;
use App\Notifications\PortalNotifier;
use App\UserRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LeaveApprovalController extends Controller
{
    /**
     * Display role-scoped approval queue for Head of Department, HR, and Director.
     */
    public function index(Request $request): View
    {
        Gate::authorize('access-leave-approvals');

        $user = $request->user();
        $role = $user->role;

        $query = LeaveApplication::with(['user.profile.department', 'leaveType', 'actions.actor'])
            ->latest();

        if ($role === UserRole::HeadOfDepartment) {
            $departmentId = $user->profile?->department_id;
            $query->where('current_stage', LeaveApplication::STAGE_SUPERVISOR)
                ->where('status', LeaveApplication::STATUS_PENDING_SUPERVISOR)
                ->where('assigned_department_id', $departmentId);
        } elseif ($role === UserRole::HrRegistryAdmin) {
            $query->where('current_stage', LeaveApplication::STAGE_HR)
                ->where('status', LeaveApplication::STATUS_PENDING_HR);
        } elseif ($role === UserRole::Director) {
            $query->where('current_stage', LeaveApplication::STAGE_DIRECTOR)
                ->where('status', LeaveApplication::STATUS_PENDING_DIRECTOR);
        } else {
            // Institutional superadmin oversight
            $query->whereIn('status', [
                LeaveApplication::STATUS_PENDING_SUPERVISOR,
                LeaveApplication::STATUS_PENDING_HR,
                LeaveApplication::STATUS_PENDING_DIRECTOR,
            ]);
        }

        $pendingApplications = $query->paginate(15);

        // Recent decisions made by this officer
        $recentActions = LeaveApprovalAction::with(['application.user', 'application.leaveType'])
            ->where('actor_id', $user->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('approvals.leave', [
            'user' => $user,
            'role' => $role,
            'pendingApplications' => $pendingApplications,
            'recentActions' => $recentActions,
        ]);
    }

    /**
     * Process an approval state machine decision with optimistic concurrency and policy checks.
     */
    public function action(Request $request, LeaveApplication $leave): RedirectResponse
    {
        Gate::authorize('access-leave-approvals');

        $user = $request->user();

        $validated = $request->validate([
            'action' => ['required', 'in:recommend,verify,approve,reject,return'],
            'comments' => ['required_if:action,reject,return', 'nullable', 'string', 'max:1000'],
            'concurrency_version' => ['required', 'integer'],
        ]);

        $action = $validated['action'];
        $comments = $validated['comments'] ?? null;
        $submittedVersion = (int) $validated['concurrency_version'];

        $result = DB::transaction(function () use ($leave, $user, $action, $comments, $submittedVersion) {
            /** @var LeaveApplication $lockedLeave */
            $lockedLeave = LeaveApplication::where('id', $leave->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Check concurrency version
            if ($lockedLeave->concurrency_version !== $submittedVersion) {
                return ['error' => 'Conflict: This application was updated by another officer or applicant. Please refresh the page.'];
            }

            // Role and stage permission rules
            if ($lockedLeave->current_stage === LeaveApplication::STAGE_SUPERVISOR) {
                if ($user->role !== UserRole::HeadOfDepartment && $user->role !== UserRole::InstitutionalSuperadmin) {
                    abort(403, 'Only the designated Head of Department can review this application at the supervisor stage.');
                }

                if ($user->role === UserRole::HeadOfDepartment && $user->profile?->department_id !== $lockedLeave->assigned_department_id) {
                    abort(403, 'Cross-department approval violation: You may only review applications from your own department.');
                }

                if (! in_array($action, ['recommend', 'reject', 'return'], true)) {
                    abort(422, 'Invalid action for supervisor stage.');
                }

                if ($action === 'recommend') {
                    if ($lockedLeave->leaveType->requires_hr_verification) {
                        $lockedLeave->current_stage = LeaveApplication::STAGE_HR;
                        $lockedLeave->status = LeaveApplication::STATUS_PENDING_HR;
                    } else {
                        $lockedLeave->current_stage = LeaveApplication::STAGE_DIRECTOR;
                        $lockedLeave->status = LeaveApplication::STATUS_PENDING_DIRECTOR;
                    }
                }
            } elseif ($lockedLeave->current_stage === LeaveApplication::STAGE_HR) {
                if ($user->role !== UserRole::HrRegistryAdmin && $user->role !== UserRole::InstitutionalSuperadmin) {
                    abort(403, 'Only HR / Registry Administrators can verify applications at the HR stage.');
                }

                if (! in_array($action, ['verify', 'reject', 'return'], true)) {
                    abort(422, 'Invalid action for HR verification stage.');
                }

                if ($action === 'verify') {
                    $lockedLeave->current_stage = LeaveApplication::STAGE_DIRECTOR;
                    $lockedLeave->status = LeaveApplication::STATUS_PENDING_DIRECTOR;
                }
            } elseif ($lockedLeave->current_stage === LeaveApplication::STAGE_DIRECTOR) {
                if ($user->role !== UserRole::Director && $user->role !== UserRole::InstitutionalSuperadmin) {
                    abort(403, 'Only an authorized Director can issue final leave decisions.');
                }

                if (! in_array($action, ['approve', 'reject', 'return'], true)) {
                    abort(422, 'Invalid action for Director approval stage.');
                }

                if ($action === 'approve') {
                    // Critical PRD Rule: UNCONFIGURED policies block final actions and balance deductions
                    $entitlement = LeaveEntitlement::where('user_id', $lockedLeave->user_id)
                        ->where('leave_type_id', $lockedLeave->leave_type_id)
                        ->where('year', $lockedLeave->start_date->year)
                        ->lockForUpdate()
                        ->first();

                    if (! $entitlement || ! $entitlement->is_policy_configured) {
                        return ['error' => 'Cannot approve leave: Institutional leave policies for this category are unconfigured. Final actions and deductions are blocked.'];
                    }

                    // Deduct balance
                    $entitlement->pending_days = max(0, $entitlement->pending_days - $lockedLeave->days_requested);
                    $entitlement->used_days = $entitlement->used_days + $lockedLeave->days_requested;
                    $entitlement->save();

                    $lockedLeave->status = LeaveApplication::STATUS_APPROVED;
                    $lockedLeave->current_stage = LeaveApplication::STAGE_COMPLETED;
                    $lockedLeave->approved_by = $user->id;
                    $lockedLeave->approved_at = now();
                    $lockedLeave->approval_notes = $comments;

                    // Generate Official Letter with an atomic, race-free reference
                    $letterYear = (int) $lockedLeave->start_date->year;
                    $floor = OfficialLetter::query()->whereYear('issued_at', $letterYear)->count();
                    $sequence = ReferenceSequence::next('official_letter_leave', $letterYear, $floor);
                    $referenceNumber = 'NFLV/EST/LEAVE/'.$letterYear.'/'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

                    $html = view('letters.leave_approval', [
                        'leave' => $lockedLeave,
                        'referenceNumber' => $referenceNumber,
                        'approver' => $user,
                    ])->render();

                    $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                    $pdfContent = $pdf->output();
                    $fileHash = hash('sha256', $pdfContent);
                    $storagePath = 'letters/'.$lockedLeave->start_date->year.'/letter_'.$lockedLeave->id.'_'.$fileHash.'.pdf';

                    Storage::disk('private')->put($storagePath, $pdfContent);

                    OfficialLetter::create([
                        'user_id' => $lockedLeave->user_id,
                        'leave_application_id' => $lockedLeave->id,
                        'reference_number' => $referenceNumber,
                        'document_type' => 'leave_approval',
                        'subject' => 'Approval of '.$lockedLeave->leaveType->name.' Application',
                        'body_html' => $html,
                        'storage_path' => $storagePath,
                        'file_hash' => $fileHash,
                        'file_size' => strlen($pdfContent),
                        'issued_by' => $user->id,
                        'issued_at' => now(),
                    ]);
                }
            } else {
                abort(422, 'This application is already completed and cannot be acted on.');
            }

            // Common rejection and return handling: release pending days back to staff
            if ($action === 'reject') {
                $entitlement = LeaveEntitlement::where('user_id', $lockedLeave->user_id)
                    ->where('leave_type_id', $lockedLeave->leave_type_id)
                    ->where('year', $lockedLeave->start_date->year)
                    ->lockForUpdate()
                    ->first();

                if ($entitlement) {
                    $entitlement->pending_days = max(0, $entitlement->pending_days - $lockedLeave->days_requested);
                    $entitlement->save();
                }

                $lockedLeave->status = LeaveApplication::STATUS_REJECTED;
                $lockedLeave->current_stage = LeaveApplication::STAGE_COMPLETED;
                $lockedLeave->rejected_by = $user->id;
                $lockedLeave->rejected_at = now();
                $lockedLeave->rejection_reason = $comments;
            } elseif ($action === 'return') {
                $entitlement = LeaveEntitlement::where('user_id', $lockedLeave->user_id)
                    ->where('leave_type_id', $lockedLeave->leave_type_id)
                    ->where('year', $lockedLeave->start_date->year)
                    ->lockForUpdate()
                    ->first();

                if ($entitlement) {
                    $entitlement->pending_days = max(0, $entitlement->pending_days - $lockedLeave->days_requested);
                    $entitlement->save();
                }

                $lockedLeave->status = LeaveApplication::STATUS_RETURNED;
                $lockedLeave->returned_by = $user->id;
                $lockedLeave->returned_at = now();
                $lockedLeave->return_reason = $comments;
            }

            // Record action in immutable audit trail
            LeaveApprovalAction::create([
                'leave_application_id' => $lockedLeave->id,
                'actor_id' => $user->id,
                'stage' => $lockedLeave->current_stage,
                'action' => $action,
                'comments' => $comments,
            ]);

            // Increment version to guard future concurrent updates
            $lockedLeave->concurrency_version = $lockedLeave->concurrency_version + 1;
            $lockedLeave->save();

            return ['success' => "Application #{$lockedLeave->id} has been processed with action: {$action}."];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        $freshLeave = $leave->fresh(['user', 'leaveType', 'letter']);

        if ($freshLeave !== null) {
            PortalNotifier::send(
                $freshLeave->user,
                'Leave application updated',
                'Your leave application status is now: '.str_replace('_', ' ', (string) $freshLeave->status).'.',
                route('leave.index'),
                'leave',
            );

            if ($action === 'approve' && $freshLeave->letter !== null) {
                PortalNotifier::send(
                    $freshLeave->user,
                    'Official letter available',
                    'Your official '.$freshLeave->leaveType->name.' approval letter is available to download.',
                    route('leave.index'),
                    'letter',
                );

                AuditLog::record('official_letter.issued', $freshLeave->letter, [
                    'reference_number' => $freshLeave->letter->reference_number,
                    'document_type' => $freshLeave->letter->document_type,
                ]);
            }

            AuditLog::record('leave.decision', $freshLeave, [
                'action' => $action,
                'stage' => $freshLeave->current_stage,
            ]);
        }

        return redirect()->route('approvals.leave.index')->with('status', $result['success']);
    }
}
