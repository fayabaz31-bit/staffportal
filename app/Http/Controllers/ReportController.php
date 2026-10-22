<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\PayslipBatch;
use App\Models\ReportExportAudit;
use App\Models\ServiceRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display the operational reports dashboard scoped to the authenticated officer's authority.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view-reports');

        $user = $request->user();
        $role = $user->role;

        $stats = [];

        if (in_array($role, [UserRole::HrRegistryAdmin, UserRole::Director, UserRole::InstitutionalSuperadmin, UserRole::AuditorReadonly], true)) {
            $stats['total_staff'] = User::where('role', UserRole::Staff)->count();
            $stats['active_staff'] = User::where('role', UserRole::Staff)->where('status', 'active')->count();
            $stats['pending_leave'] = LeaveApplication::whereIn('status', [
                LeaveApplication::STATUS_SUBMITTED,
                LeaveApplication::STATUS_PENDING_SUPERVISOR,
                LeaveApplication::STATUS_PENDING_HR,
                LeaveApplication::STATUS_PENDING_DIRECTOR,
            ])->count();
            $stats['approved_leave'] = LeaveApplication::where('status', LeaveApplication::STATUS_APPROVED)->count();
        }

        if (in_array($role, [UserRole::BursaryAdmin, UserRole::InstitutionalSuperadmin, UserRole::AuditorReadonly], true)) {
            $stats['payslip_batches'] = PayslipBatch::count();
            $stats['published_batches'] = PayslipBatch::where('status', PayslipBatch::STATUS_PUBLISHED)->count();
            $stats['bursary_requests'] = ServiceRequest::where('unit', ServiceRequest::UNIT_BURSARY)->count();
        }

        if ($role === UserRole::HeadOfDepartment) {
            $deptId = $user->profile?->department_id;
            $stats['dept_staff'] = User::whereHas('profile', fn ($q) => $q->where('department_id', $deptId))->count();
            $stats['dept_pending_leave'] = LeaveApplication::where('assigned_department_id', $deptId)
                ->where('status', LeaveApplication::STATUS_PENDING_SUPERVISOR)
                ->count();
        }

        $stats['open_requests'] = ServiceRequest::where('status', ServiceRequest::STATUS_OPEN)->count();
        $stats['resolved_requests'] = ServiceRequest::where('status', ServiceRequest::STATUS_RESOLVED)->count();

        return view('reports.index', [
            'user' => $user,
            'role' => $role,
            'stats' => $stats,
        ]);
    }

    /**
     * Export permitted summary report with audit logging and privacy enforcement.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('view-reports');

        $user = $request->user();
        $role = $user->role;

        $validated = $request->validate([
            'report_type' => ['required', 'string', 'in:personnel_summary,leave_summary,service_requests_summary,payslip_reconciliation'],
        ]);

        $reportType = $validated['report_type'];

        // Enforce role-level scope authorization on export
        if ($reportType === 'personnel_summary' && ! in_array($role, [UserRole::HrRegistryAdmin, UserRole::HeadOfDepartment, UserRole::Director, UserRole::InstitutionalSuperadmin, UserRole::AuditorReadonly], true)) {
            abort(403, 'Unauthorized to export personnel summary report.');
        }

        if ($reportType === 'payslip_reconciliation' && ! in_array($role, [UserRole::BursaryAdmin, UserRole::InstitutionalSuperadmin, UserRole::AuditorReadonly], true)) {
            abort(403, 'Unauthorized to export payroll reconciliation report.');
        }

        // Record immutable audit entry
        ReportExportAudit::create([
            'actor_id' => $user->id,
            'report_type' => $reportType,
            'format' => 'csv',
            'filters' => $request->except(['_token']),
            'ip_address' => $request->ip(),
        ]);

        $fileName = "Report_{$reportType}_".now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($reportType, $user, $role): void {
            $handle = fopen('php://output', 'w');

            if ($reportType === 'personnel_summary') {
                fputcsv($handle, ['Staff ID', 'Name', 'Official Email', 'Department', 'Employment Status', 'Account Status']);

                $query = User::with('profile.department')->where('role', UserRole::Staff);
                if ($role === UserRole::HeadOfDepartment) {
                    $deptId = $user->profile?->department_id;
                    $query->whereHas('profile', fn ($q) => $q->where('department_id', $deptId));
                }

                $query->chunk(100, function ($users) use ($handle): void {
                    foreach ($users as $u) {
                        fputcsv($handle, [
                            $u->staff_number,
                            $u->name,
                            $u->email,
                            $u->profile?->department?->name ?? 'Unassigned',
                            $u->profile?->employment_status ?? 'Active',
                            $u->status,
                        ]);
                    }
                });
            } elseif ($reportType === 'leave_summary') {
                fputcsv($handle, ['Application ID', 'Staff ID', 'Staff Name', 'Leave Category', 'Start Date', 'End Date', 'Days', 'Status', 'Current Stage']);

                $query = LeaveApplication::with(['user', 'leaveType']);
                if ($role === UserRole::HeadOfDepartment) {
                    $deptId = $user->profile?->department_id;
                    $query->where('assigned_department_id', $deptId);
                }

                $query->chunk(100, function ($apps) use ($handle): void {
                    foreach ($apps as $app) {
                        fputcsv($handle, [
                            $app->id,
                            $app->user->staff_number,
                            $app->user->name,
                            $app->leaveType->name,
                            $app->start_date->format('Y-m-d'),
                            $app->end_date->format('Y-m-d'),
                            $app->days_requested,
                            $app->status,
                            $app->current_stage,
                        ]);
                    }
                });
            } elseif ($reportType === 'service_requests_summary') {
                fputcsv($handle, ['Reference', 'Unit', 'Category', 'Subject', 'Priority', 'Status', 'Submitted At', 'Resolved At']);

                $query = ServiceRequest::latest();
                if ($role === UserRole::HrRegistryAdmin) {
                    $query->where('unit', ServiceRequest::UNIT_REGISTRY);
                } elseif ($role === UserRole::BursaryAdmin) {
                    $query->where('unit', ServiceRequest::UNIT_BURSARY);
                }

                $query->chunk(100, function ($requests) use ($handle): void {
                    foreach ($requests as $r) {
                        fputcsv($handle, [
                            $r->tracking_reference,
                            strtoupper($r->unit),
                            $r->category,
                            $r->subject,
                            strtoupper($r->priority),
                            strtoupper($r->status),
                            $r->created_at->format('Y-m-d H:i'),
                            $r->resolved_at?->format('Y-m-d H:i') ?? '—',
                        ]);
                    }
                });
            } elseif ($reportType === 'payslip_reconciliation') {
                fputcsv($handle, ['Batch Number', 'Payroll Period', 'Total Files', 'Matched Records', 'Quarantined Exceptions', 'Status', 'Uploaded At']);

                PayslipBatch::chunk(100, function ($batches) use ($handle): void {
                    foreach ($batches as $b) {
                        fputcsv($handle, [
                            $b->batch_number,
                            $b->payroll_period,
                            $b->total_files_count,
                            $b->matched_records_count,
                            $b->unmatched_records_count,
                            strtoupper($b->status),
                            $b->created_at->format('Y-m-d H:i'),
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
