<?php

namespace App\Support;

use App\DutyCatalog;
use App\Models\AuditLog;
use App\Models\CorrespondenceDocument;
use App\Models\LeaveApplication;
use App\Models\Memo;
use App\Models\PayslipBatch;
use App\Models\ProfileChangeRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkAssignment;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Role-scoped dashboard metrics.
 *
 * Every figure is derived from real rows in the database; nothing here is
 * invented, estimated or hard-coded. Metrics are scoped to what the viewer is
 * authorised to see (Master Prompt §E: authorise by action and scope), so a
 * department lead sees their department, a unit officer sees their unit, and an
 * institutional viewer sees the institution.
 *
 * @phpstan-type KpiTile array{label: string, value: int|string, hint: string, tone: string, route?: string}
 * @phpstan-type ChartPanel array{title: string, description: string, type: string, labels: list<string>, series: list<array{label: string, values: list<int>}>, palette: list<string>}
 */
class DashboardMetrics
{
    /**
     * The viewer context derived from the signed-in account.
     */
    public function __construct(private readonly User $user) {}

    /**
     * @return array{
     *     actionQueue: list<array{label: string, detail: string, count: int, route: string, tone: string}>,
     *     kpis: list<KpiTile>,
     *     charts: list<ChartPanel>
     * }
     */
    public function forRole(): array
    {
        return match ($this->user->role) {
            UserRole::Staff => $this->staff(),
            UserRole::HrRegistryAdmin => $this->registry(),
            UserRole::BursaryAdmin => $this->bursary(),
            UserRole::HeadOfDepartment => $this->headOfDepartment(),
            UserRole::Director => $this->director(),
            UserRole::IctSuperadmin => $this->ict(),
            UserRole::InstitutionalSuperadmin => $this->institutional(),
            UserRole::AuditorReadonly => $this->auditor(),
        };
    }

    // ---------------------------------------------------------------- Staff

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function staff(): array
    {
        $leave = LeaveApplication::query()->where('user_id', $this->user->id);

        $pending = (clone $leave)->whereIn('status', $this->pendingLeaveStatuses())->count();
        $approved = (clone $leave)->where('status', LeaveApplication::STATUS_APPROVED)->count();
        $daysTaken = (int) (clone $leave)->where('status', LeaveApplication::STATUS_APPROVED)->sum('days_requested');

        $payslips = DB::table('payslips')->where('user_id', $this->user->id)->count();
        $unread = $this->user->unreadNotifications()->count();

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Leave applications awaiting a decision',
                    'detail' => 'Applications you have submitted that are still moving through approval.',
                    'count' => $pending,
                    'route' => route('leave.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Unread notifications',
                    'detail' => 'Portal updates you have not opened yet.',
                    'count' => $unread,
                    'route' => route('notifications.index'),
                    'tone' => 'info',
                ],
            ]),
            'kpis' => [
                ['label' => 'Leave pending', 'value' => $pending, 'hint' => 'In the approval workflow', 'tone' => 'attention', 'route' => route('leave.index')],
                ['label' => 'Leave approved', 'value' => $approved, 'hint' => 'All time', 'tone' => 'positive', 'route' => route('leave.index')],
                ['label' => 'Leave days taken', 'value' => $daysTaken, 'hint' => 'Approved applications', 'tone' => 'neutral'],
                ['label' => 'Payslips available', 'value' => $payslips, 'hint' => 'Published to you', 'tone' => 'positive', 'route' => route('payslips.index')],
            ],
            'charts' => [
                [
                    'title' => 'Your leave applications',
                    'description' => 'A breakdown of every leave application on your record.',
                    'type' => 'doughnut',
                    'labels' => ['Pending', 'Approved', 'Rejected', 'Returned', 'Cancelled'],
                    'series' => [[
                        'label' => 'Applications',
                        'values' => $this->countByStatus($leave, [
                            'pending' => $this->pendingLeaveStatuses(),
                            'approved' => [LeaveApplication::STATUS_APPROVED],
                            'rejected' => [LeaveApplication::STATUS_REJECTED],
                            'returned' => [LeaveApplication::STATUS_RETURNED],
                            'cancelled' => [LeaveApplication::STATUS_CANCELLED],
                        ]),
                    ]],
                    'palette' => ['gold', 'fern', 'coral', 'blue', 'muted'],
                ],
            ],
        ];
    }

    // ------------------------------------------------------------- Registry

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function registry(): array
    {
        $user = $this->user;
        $isRegistrar = $user->hasDuty(DutyCatalog::REGISTRY_REGISTRAR_APPROVE);
        $isHeadOfHr = $user->hasDuty(DutyCatalog::REGISTRY_HEAD_REVIEW);

        $pendingChanges = ProfileChangeRequest::query()
            ->where('status', ProfileChangeRequest::STATUS_PENDING)
            ->count();

        $personnel = DB::table('staff_profiles')->count();
        $registryDesk = ServiceRequest::query()->where('unit', ServiceRequest::UNIT_REGISTRY);
        $openDesk = (clone $registryDesk)->whereIn('status', $this->openServiceStatuses())->count();

        $pendingLeaveVerification = LeaveApplication::query()
            ->where('status', LeaveApplication::STATUS_PENDING_HR)
            ->count();

        $myOpenAssignments = WorkAssignment::query()
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', [WorkAssignment::STATUS_COMPLETED, WorkAssignment::STATUS_CANCELLED])
            ->count();

        $assignmentsToClose = WorkAssignment::query()
            ->where('assigned_by', $user->id)
            ->where('status', WorkAssignment::STATUS_SUBMITTED)
            ->count();

        $headReviewQueue = $isHeadOfHr
            ? CorrespondenceDocument::query()
                ->whereIn('status', [CorrespondenceDocument::STATUS_SUBMITTED, CorrespondenceDocument::STATUS_UNDER_REVIEW])
                ->whereHas('workflowInstance.currentStep', fn (Builder $query) => $query
                    ->where('approver_duty', DutyCatalog::REGISTRY_HEAD_REVIEW))
                ->count()
            : 0;

        $registrarReviewQueue = $isRegistrar
            ? CorrespondenceDocument::query()
                ->whereIn('status', [CorrespondenceDocument::STATUS_SUBMITTED, CorrespondenceDocument::STATUS_UNDER_REVIEW])
                ->whereHas('workflowInstance.currentStep', fn (Builder $query) => $query
                    ->where('approver_duty', DutyCatalog::REGISTRY_REGISTRAR_APPROVE))
                ->count()
            : 0;

        $approvedNotIssued = $isRegistrar
            ? CorrespondenceDocument::query()->where('status', CorrespondenceDocument::STATUS_APPROVED)->count()
            : 0;

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Profile-change requests to review',
                    'detail' => 'Proposed updates that must be approved before official records change.',
                    'count' => $pendingChanges,
                    'route' => route('registry.profile-change-requests.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Duty assignments to close',
                    'detail' => 'Work your officers have submitted and are waiting for you to complete.',
                    'count' => $assignmentsToClose,
                    'route' => route('assignments.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Correspondence for Head of HR review',
                    'detail' => 'Documents waiting at the Head of HR stage of their configured workflow.',
                    'count' => $headReviewQueue,
                    'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_UNDER_REVIEW]),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Correspondence awaiting Registrar approval',
                    'detail' => 'Documents recommended by the Head of HR and waiting for final Registry approval.',
                    'count' => $registrarReviewQueue,
                    'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_UNDER_REVIEW]),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Approved correspondence not yet issued',
                    'detail' => 'Approval is complete but the document has not been issued or delivered.',
                    'count' => $approvedNotIssued,
                    'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_APPROVED]),
                    'tone' => 'critical',
                ],
                [
                    'label' => 'My open duty assignments',
                    'detail' => 'Work assigned to you that is not yet completed.',
                    'count' => $myOpenAssignments,
                    'route' => route('assignments.index'),
                    'tone' => 'info',
                ],
                [
                    'label' => 'Leave applications to verify',
                    'detail' => 'Applications at the HR verification stage.',
                    'count' => $pendingLeaveVerification,
                    'route' => route('approvals.leave.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Open registry service requests',
                    'detail' => 'Enquiries from staff that are not yet resolved.',
                    'count' => $openDesk,
                    'route' => route('registry.service-requests.index'),
                    'tone' => 'info',
                ],
            ]),
            'kpis' => [
                ['label' => 'Pending profile changes', 'value' => $pendingChanges, 'hint' => 'Awaiting review', 'tone' => 'attention', 'route' => route('registry.profile-change-requests.index')],
                ['label' => 'My open duty assignments', 'value' => $myOpenAssignments, 'hint' => 'Assigned to you', 'tone' => $myOpenAssignments > 0 ? 'attention' : 'neutral', 'route' => route('assignments.index')],
                ['label' => 'Correspondence for Head of HR review', 'value' => $headReviewQueue, 'hint' => 'Configured stage', 'tone' => 'attention', 'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_UNDER_REVIEW])],
                ['label' => 'Correspondence awaiting Registrar approval', 'value' => $registrarReviewQueue, 'hint' => 'Final Registry stage', 'tone' => 'attention', 'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_UNDER_REVIEW])],
                ['label' => 'Approved, not issued', 'value' => $approvedNotIssued, 'hint' => 'Approval and delivery are separate', 'tone' => $approvedNotIssued > 0 ? 'critical' : 'neutral', 'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_APPROVED])],
                ['label' => 'Leave to verify', 'value' => $pendingLeaveVerification, 'hint' => 'At HR stage', 'tone' => 'attention', 'route' => route('approvals.leave.index')],
                ['label' => 'Personnel records', 'value' => $personnel, 'hint' => 'On the register', 'tone' => 'neutral', 'route' => route('registry.personnel.index')],
                ['label' => 'Open registry service requests', 'value' => $openDesk, 'hint' => 'Not yet resolved', 'tone' => 'info', 'route' => route('registry.service-requests.index')],
            ],
            'charts' => [
                [
                    'title' => 'Correspondence pipeline',
                    'description' => 'Registry correspondence at each stage of its lifecycle.',
                    'type' => 'bar',
                    'labels' => ['Draft', 'Under review', 'Approved', 'Issued', 'Returned'],
                    'series' => [[
                        'label' => 'Documents',
                        'values' => [
                            CorrespondenceDocument::query()->where('status', CorrespondenceDocument::STATUS_DRAFT)->count(),
                            CorrespondenceDocument::query()->whereIn('status', [
                                CorrespondenceDocument::STATUS_SUBMITTED,
                                CorrespondenceDocument::STATUS_UNDER_REVIEW,
                                CorrespondenceDocument::STATUS_AWAITING_APPROVAL,
                            ])->count(),
                            CorrespondenceDocument::query()->where('status', CorrespondenceDocument::STATUS_APPROVED)->count(),
                            CorrespondenceDocument::query()->where('status', CorrespondenceDocument::STATUS_ISSUED)->count(),
                            CorrespondenceDocument::query()->where('status', CorrespondenceDocument::STATUS_RETURNED)->count(),
                        ],
                    ]],
                    'palette' => ['pine'],
                ],
                [
                    'title' => 'Profile-change requests',
                    'description' => 'Every profile-change request by its current state.',
                    'type' => 'doughnut',
                    'labels' => ['Pending', 'Approved', 'Rejected'],
                    'series' => [[
                        'label' => 'Requests',
                        'values' => $this->countProfileChanges(),
                    ]],
                    'palette' => ['gold', 'fern', 'coral'],
                ],
                [
                    'title' => 'Registry service desk',
                    'description' => 'Staff enquiries handled by the Registry desk.',
                    'type' => 'bar',
                    'labels' => ['Open', 'In progress', 'Escalated', 'Resolved', 'Closed'],
                    'series' => [[
                        'label' => 'Requests',
                        'values' => $this->countServiceByStatus(ServiceRequest::UNIT_REGISTRY),
                    ]],
                    'palette' => ['pine'],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------- Bursary

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function bursary(): array
    {
        $staged = PayslipBatch::query()->where('status', PayslipBatch::STATUS_STAGED)->count();
        $quarantined = PayslipBatch::query()->where('status', PayslipBatch::STATUS_QUARANTINED)->count();
        $published = PayslipBatch::query()->where('status', PayslipBatch::STATUS_PUBLISHED)->count();

        $desk = ServiceRequest::query()->where('unit', ServiceRequest::UNIT_BURSARY);
        $openDesk = (clone $desk)->whereIn('status', $this->openServiceStatuses())->count();

        $releasedThisMonth = PayslipBatch::query()
            ->where('status', PayslipBatch::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Batches awaiting release',
                    'detail' => 'Validated batches that need a release officer (never the uploader).',
                    'count' => $staged,
                    'route' => route('bursary.payslips.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Quarantined batches',
                    'detail' => 'Batches held back by reconciliation errors.',
                    'count' => $quarantined,
                    'route' => route('bursary.payslips.index'),
                    'tone' => 'critical',
                ],
                [
                    'label' => 'Open bursary service requests',
                    'detail' => 'Salary and deduction enquiries not yet resolved.',
                    'count' => $openDesk,
                    'route' => route('bursary.service-requests.index'),
                    'tone' => 'info',
                ],
            ]),
            'kpis' => [
                ['label' => 'Batches awaiting release', 'value' => $staged, 'hint' => 'Staged and validated', 'tone' => 'attention', 'route' => route('bursary.payslips.index')],
                ['label' => 'Quarantined', 'value' => $quarantined, 'hint' => 'Unresolved errors', 'tone' => $quarantined > 0 ? 'critical' : 'neutral', 'route' => route('bursary.payslips.index')],
                ['label' => 'Released this month', 'value' => $releasedThisMonth, 'hint' => 'Published batches', 'tone' => 'positive', 'route' => route('bursary.payslips.index')],
                ['label' => 'Open enquiries', 'value' => $openDesk, 'hint' => 'Bursary desk', 'tone' => 'info', 'route' => route('bursary.service-requests.index')],
            ],
            'charts' => [
                [
                    'title' => 'Payslip batch states',
                    'description' => 'Every staged payroll batch by its current state.',
                    'type' => 'doughnut',
                    'labels' => ['Staged', 'Quarantined', 'Published', 'Withdrawn'],
                    'series' => [[
                        'label' => 'Batches',
                        'values' => [
                            $staged,
                            $quarantined,
                            $published,
                            PayslipBatch::query()->where('status', PayslipBatch::STATUS_WITHDRAWN)->count(),
                        ],
                    ]],
                    'palette' => ['gold', 'coral', 'fern', 'muted'],
                ],
                [
                    'title' => 'Bursary service desk',
                    'description' => 'Salary and deduction enquiries by state.',
                    'type' => 'bar',
                    'labels' => ['Open', 'In progress', 'Escalated', 'Resolved', 'Closed'],
                    'series' => [[
                        'label' => 'Requests',
                        'values' => $this->countServiceByStatus(ServiceRequest::UNIT_BURSARY),
                    ]],
                    'palette' => ['pine'],
                ],
            ],
        ];
    }

    // ------------------------------------------------------- Head of Dept

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function headOfDepartment(): array
    {
        $departmentId = $this->user->profile?->department_id;

        $departmentLeave = LeaveApplication::query()
            ->when($departmentId !== null, fn ($query) => $query->where('assigned_department_id', $departmentId));

        $toRecommend = (clone $departmentLeave)
            ->where('status', LeaveApplication::STATUS_PENDING_SUPERVISOR)
            ->count();

        $teamSize = $departmentId === null
            ? 0
            : DB::table('staff_profiles')->where('department_id', $departmentId)->count();

        $dutiesInUnit = 0;

        foreach (User::query()->with('staffProfile')->get() as $candidate) {
            if ($candidate->canManageDutiesOf($this->user)) {
                $dutiesInUnit += $candidate->duties()->count();
            }
        }

        $memosPublished = Memo::query()->where('status', Memo::STATUS_PUBLISHED)->count();

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Leave recommendations for your department',
                    'detail' => 'Applications waiting for your recommendation at the supervisor stage.',
                    'count' => $toRecommend,
                    'route' => route('approvals.leave.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Duties to review in your team',
                    'detail' => 'Responsibilities currently assigned across your department.',
                    'count' => $dutiesInUnit,
                    'route' => route('ict.team-duties.index'),
                    'tone' => 'info',
                ],
            ]),
            'kpis' => [
                ['label' => 'Awaiting recommendation', 'value' => $toRecommend, 'hint' => 'Supervisor stage', 'tone' => 'attention', 'route' => route('approvals.leave.index')],
                ['label' => 'Team size', 'value' => $teamSize, 'hint' => 'In your department', 'tone' => 'neutral'],
                ['label' => 'Duties held in team', 'value' => $dutiesInUnit, 'hint' => 'Across your officers', 'tone' => 'info', 'route' => route('ict.team-duties.index')],
                ['label' => 'Published memos', 'value' => $memosPublished, 'hint' => 'Institution-wide', 'tone' => 'positive', 'route' => route('memos.index')],
            ],
            'charts' => [
                [
                    'title' => 'Departmental leave pipeline',
                    'description' => 'Applications from your department at each workflow stage.',
                    'type' => 'bar',
                    'labels' => ['Supervisor', 'HR', 'Director', 'Approved', 'Rejected'],
                    'series' => [[
                        'label' => 'Applications',
                        'values' => [
                            (clone $departmentLeave)->where('status', LeaveApplication::STATUS_PENDING_SUPERVISOR)->count(),
                            (clone $departmentLeave)->where('status', LeaveApplication::STATUS_PENDING_HR)->count(),
                            (clone $departmentLeave)->where('status', LeaveApplication::STATUS_PENDING_DIRECTOR)->count(),
                            (clone $departmentLeave)->where('status', LeaveApplication::STATUS_APPROVED)->count(),
                            (clone $departmentLeave)->where('status', LeaveApplication::STATUS_REJECTED)->count(),
                        ],
                    ]],
                    'palette' => ['pine'],
                ],
            ],
        ];
    }

    // ------------------------------------------------------------- Director

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function director(): array
    {
        $awaitingDecision = LeaveApplication::query()
            ->where('status', LeaveApplication::STATUS_PENDING_DIRECTOR)
            ->count();

        $pendingHr = LeaveApplication::query()
            ->where('status', LeaveApplication::STATUS_PENDING_HR)
            ->count();

        $publishedMemos = Memo::query()->where('status', Memo::STATUS_PUBLISHED)->count();

        $lettersIssued = DB::table('official_letters')->count();

        $directorCorrespondence = CorrespondenceDocument::query()
            ->whereIn('status', [CorrespondenceDocument::STATUS_SUBMITTED, CorrespondenceDocument::STATUS_UNDER_REVIEW])
            ->whereHas('workflowInstance.currentStep', fn (Builder $query) => $query
                ->where('approver_duty', DutyCatalog::REGISTRY_DIRECTOR_ESCALATION))
            ->count();

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Reserved correspondence awaiting your decision',
                    'detail' => 'Registry matters the institution reserves to the Director/CEO.',
                    'count' => $directorCorrespondence,
                    'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_UNDER_REVIEW]),
                    'tone' => 'critical',
                ],
                [
                    'label' => 'Leave decisions awaiting you',
                    'detail' => 'Applications at the final approval stage.',
                    'count' => $awaitingDecision,
                    'route' => route('approvals.leave.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Applications still in verification',
                    'detail' => 'Cases progressing through HR verification.',
                    'count' => $pendingHr,
                    'route' => route('approvals.leave.index'),
                    'tone' => 'info',
                ],
            ]),
            'kpis' => [
                ['label' => 'Reserved correspondence', 'value' => $directorCorrespondence, 'hint' => 'Awaiting your decision', 'tone' => $directorCorrespondence > 0 ? 'critical' : 'neutral', 'route' => route('registry.correspondence.index', ['status' => CorrespondenceDocument::STATUS_UNDER_REVIEW])],
                ['label' => 'Awaiting your decision', 'value' => $awaitingDecision, 'hint' => 'Director stage', 'tone' => 'attention', 'route' => route('approvals.leave.index')],
                ['label' => 'In HR verification', 'value' => $pendingHr, 'hint' => 'Earlier stage', 'tone' => 'info', 'route' => route('approvals.leave.index')],
                ['label' => 'Official letters issued', 'value' => $lettersIssued, 'hint' => 'All time', 'tone' => 'positive'],
                ['label' => 'Published memos', 'value' => $publishedMemos, 'hint' => 'Active correspondence', 'tone' => 'neutral', 'route' => route('memos.index')],
            ],
            'charts' => [
                [
                    'title' => 'Institutional approval pipeline',
                    'description' => 'All leave applications by workflow stage.',
                    'type' => 'bar',
                    'labels' => ['Supervisor', 'HR', 'Director', 'Approved', 'Rejected'],
                    'series' => [[
                        'label' => 'Applications',
                        'values' => [
                            LeaveApplication::query()->where('status', LeaveApplication::STATUS_PENDING_SUPERVISOR)->count(),
                            $pendingHr,
                            $awaitingDecision,
                            LeaveApplication::query()->where('status', LeaveApplication::STATUS_APPROVED)->count(),
                            LeaveApplication::query()->where('status', LeaveApplication::STATUS_REJECTED)->count(),
                        ],
                    ]],
                    'palette' => ['pine'],
                ],
            ],
        ];
    }

    // ------------------------------------------------------------- ICT

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function ict(): array
    {
        $active = User::query()->where('status', 'active')->count();
        $suspended = User::query()->where('status', 'suspended')->count();
        $deactivated = User::query()->where('status', 'deactivated')->count();

        $openTickets = ServiceRequest::query()
            ->where('unit', ServiceRequest::UNIT_ICT)
            ->whereIn('status', $this->openServiceStatuses())
            ->count();

        $securityEvents = AuditLog::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Open ICT service requests',
                    'detail' => 'Tickets that are not yet resolved.',
                    'count' => $openTickets,
                    'route' => route('ict.service-requests.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Suspended accounts',
                    'detail' => 'Accounts that are temporarily blocked and may need review.',
                    'count' => $suspended,
                    'route' => route('ict.accounts.index'),
                    'tone' => 'critical',
                ],
            ]),
            'kpis' => [
                ['label' => 'Active accounts', 'value' => $active, 'hint' => 'Currently signed-in eligible', 'tone' => 'positive', 'route' => route('ict.accounts.index')],
                ['label' => 'Suspended accounts', 'value' => $suspended, 'hint' => 'Temporarily blocked', 'tone' => $suspended > 0 ? 'critical' : 'neutral', 'route' => route('ict.accounts.index')],
                ['label' => 'Open ICT tickets', 'value' => $openTickets, 'hint' => 'Unresolved', 'tone' => 'attention', 'route' => route('ict.service-requests.index')],
                ['label' => 'Audit events (7 days)', 'value' => $securityEvents, 'hint' => 'Recorded activity', 'tone' => 'info', 'route' => route('audit.index')],
            ],
            'charts' => [
                [
                    'title' => 'Account lifecycle',
                    'description' => 'Every portal account by its current lifecycle state.',
                    'type' => 'doughnut',
                    'labels' => ['Active', 'Suspended', 'Deactivated'],
                    'series' => [[
                        'label' => 'Accounts',
                        'values' => [$active, $suspended, $deactivated],
                    ]],
                    'palette' => ['fern', 'gold', 'coral'],
                ],
                [
                    'title' => 'ICT service desk',
                    'description' => 'Technical tickets by state.',
                    'type' => 'bar',
                    'labels' => ['Open', 'In progress', 'Escalated', 'Resolved', 'Closed'],
                    'series' => [[
                        'label' => 'Requests',
                        'values' => $this->countServiceByStatus(ServiceRequest::UNIT_ICT),
                    ]],
                    'palette' => ['pine'],
                ],
            ],
        ];
    }

    // ----------------------------------------------------- Institutional

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function institutional(): array
    {
        $accounts = User::query()->where('status', 'active')->count();
        $pendingChanges = ProfileChangeRequest::query()->where('status', ProfileChangeRequest::STATUS_PENDING)->count();
        $awaitingDecision = LeaveApplication::query()->where('status', LeaveApplication::STATUS_PENDING_DIRECTOR)->count();
        $auditEvents = AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count();

        $openRequests = ServiceRequest::query()->whereIn('status', $this->openServiceStatuses())->count();

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Open service requests institution-wide',
                    'detail' => 'Enquiries across all desks that are not yet resolved.',
                    'count' => $openRequests,
                    'route' => route('reports.index'),
                    'tone' => 'attention',
                ],
                [
                    'label' => 'Profile-change requests pending',
                    'detail' => 'Governance-sensitive record changes awaiting review.',
                    'count' => $pendingChanges,
                    'route' => route('registry.profile-change-requests.index'),
                    'tone' => 'info',
                ],
            ]),
            'kpis' => [
                ['label' => 'Active accounts', 'value' => $accounts, 'hint' => 'Institution-wide', 'tone' => 'positive', 'route' => route('ict.accounts.index')],
                ['label' => 'Open service requests', 'value' => $openRequests, 'hint' => 'All desks', 'tone' => 'attention'],
                ['label' => 'Awaiting final decision', 'value' => $awaitingDecision, 'hint' => 'Director stage', 'tone' => 'info', 'route' => route('approvals.leave.index')],
                ['label' => 'Audit events (7 days)', 'value' => $auditEvents, 'hint' => 'Recorded activity', 'tone' => 'neutral', 'route' => route('audit.index')],
            ],
            'charts' => [
                [
                    'title' => 'Enquiries by service desk',
                    'description' => 'Open and total enquiries routed to each service desk.',
                    'type' => 'bar',
                    'labels' => ['Registry', 'Bursary', 'ICT'],
                    'series' => [
                        [
                            'label' => 'Open',
                            'values' => [
                                ServiceRequest::query()->where('unit', ServiceRequest::UNIT_REGISTRY)->whereIn('status', $this->openServiceStatuses())->count(),
                                ServiceRequest::query()->where('unit', ServiceRequest::UNIT_BURSARY)->whereIn('status', $this->openServiceStatuses())->count(),
                                ServiceRequest::query()->where('unit', ServiceRequest::UNIT_ICT)->whereIn('status', $this->openServiceStatuses())->count(),
                            ],
                        ],
                        [
                            'label' => 'Resolved',
                            'values' => [
                                ServiceRequest::query()->where('unit', ServiceRequest::UNIT_REGISTRY)->whereIn('status', $this->closedServiceStatuses())->count(),
                                ServiceRequest::query()->where('unit', ServiceRequest::UNIT_BURSARY)->whereIn('status', $this->closedServiceStatuses())->count(),
                                ServiceRequest::query()->where('unit', ServiceRequest::UNIT_ICT)->whereIn('status', $this->closedServiceStatuses())->count(),
                            ],
                        ],
                    ],
                    'palette' => ['gold', 'fern'],
                ],
                [
                    'title' => 'Account lifecycle',
                    'description' => 'Every portal account by its current lifecycle state.',
                    'type' => 'doughnut',
                    'labels' => ['Active', 'Suspended', 'Deactivated'],
                    'series' => [[
                        'label' => 'Accounts',
                        'values' => [
                            User::query()->where('status', 'active')->count(),
                            User::query()->where('status', 'suspended')->count(),
                            User::query()->where('status', 'deactivated')->count(),
                        ],
                    ]],
                    'palette' => ['fern', 'gold', 'coral'],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------- Auditor

    /**
     * @return array{actionQueue: list<array<string, mixed>>, kpis: list<KpiTile>, charts: list<ChartPanel>}
     */
    private function auditor(): array
    {
        $auditEvents = AuditLog::query()->count();
        $lastSevenDays = AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count();
        $dutyChanges = AuditLog::query()
            ->whereIn('action', ['account.duty_granted', 'account.duty_revoked', 'account.role_changed'])
            ->count();
        $accountEvents = AuditLog::query()
            ->where('action', 'like', 'account.%')
            ->count();

        return [
            'actionQueue' => $this->queue([
                [
                    'label' => 'Account and access changes',
                    'detail' => 'Lifecycle, role and duty changes recorded in the audit trail.',
                    'count' => $accountEvents,
                    'route' => route('audit.index'),
                    'tone' => 'info',
                ],
            ]),
            'kpis' => [
                ['label' => 'Audit entries', 'value' => $auditEvents, 'hint' => 'All time, append-only', 'tone' => 'neutral', 'route' => route('audit.index')],
                ['label' => 'Events (7 days)', 'value' => $lastSevenDays, 'hint' => 'Recent activity', 'tone' => 'info', 'route' => route('audit.index')],
                ['label' => 'Duty & role changes', 'value' => $dutyChanges, 'hint' => 'Access governance', 'tone' => 'attention', 'route' => route('audit.index')],
                ['label' => 'Account events', 'value' => $accountEvents, 'hint' => 'Lifecycle activity', 'tone' => 'neutral', 'route' => route('audit.index')],
            ],
            'charts' => [
                [
                    'title' => 'Audit activity',
                    'description' => 'Recorded events across the portal by category.',
                    'type' => 'bar',
                    'labels' => ['Accounts', 'Duties & roles', 'Documents', 'Service desk'],
                    'series' => [[
                        'label' => 'Events',
                        'values' => [
                            $accountEvents,
                            $dutyChanges,
                            AuditLog::query()->where('action', 'like', 'payslip%')->orWhere('action', 'like', 'letter%')->count(),
                            AuditLog::query()->where('action', 'like', 'service_request%')->count(),
                        ],
                    ]],
                    'palette' => ['pine'],
                ],
            ],
        ];
    }

    // ------------------------------------------------------------- Helpers

    /**
     * Keep only queue entries with work outstanding, most urgent first.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    private function queue(array $entries): array
    {
        $rank = ['critical' => 0, 'attention' => 1, 'info' => 2];

        $entries = array_values(array_filter($entries, fn (array $entry): bool => $entry['count'] > 0));

        usort($entries, fn (array $a, array $b): int => ($rank[$a['tone']] ?? 9) <=> ($rank[$b['tone']] ?? 9));

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function pendingLeaveStatuses(): array
    {
        return [
            LeaveApplication::STATUS_SUBMITTED,
            LeaveApplication::STATUS_PENDING_SUPERVISOR,
            LeaveApplication::STATUS_PENDING_HR,
            LeaveApplication::STATUS_PENDING_DIRECTOR,
        ];
    }

    /**
     * @return list<string>
     */
    private function openServiceStatuses(): array
    {
        return [
            ServiceRequest::STATUS_OPEN,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_ESCALATED,
        ];
    }

    /**
     * @return list<string>
     */
    private function closedServiceStatuses(): array
    {
        return [
            ServiceRequest::STATUS_RESOLVED,
            ServiceRequest::STATUS_CLOSED,
        ];
    }

    /**
     * Count a query in one pass using conditional aggregation.
     *
     * @param  array<string, list<string>>  $groups
     * @return list<int>
     */
    private function countByStatus(Builder $query, array $groups): array
    {
        $counts = [];

        foreach ($groups as $statuses) {
            $counts[] = (clone $query)->whereIn('status', $statuses)->count();
        }

        return $counts;
    }

    /**
     * @return list<int>
     */
    private function countProfileChanges(): array
    {
        $query = ProfileChangeRequest::query();

        return [
            (clone $query)->where('status', ProfileChangeRequest::STATUS_PENDING)->count(),
            (clone $query)->where('status', ProfileChangeRequest::STATUS_APPROVED)->count(),
            (clone $query)->where('status', ProfileChangeRequest::STATUS_REJECTED)->count(),
        ];
    }

    /**
     * @return list<int>
     */
    private function countServiceByStatus(string $unit): array
    {
        $query = ServiceRequest::query()->where('unit', $unit);

        return [
            (clone $query)->where('status', ServiceRequest::STATUS_OPEN)->count(),
            (clone $query)->where('status', ServiceRequest::STATUS_IN_PROGRESS)->count(),
            (clone $query)->where('status', ServiceRequest::STATUS_ESCALATED)->count(),
            (clone $query)->where('status', ServiceRequest::STATUS_RESOLVED)->count(),
            (clone $query)->where('status', ServiceRequest::STATUS_CLOSED)->count(),
        ];
    }

    /**
     * Duty-lead coordination figures for the team page (kept here so the same
     * scoping rules apply wherever they are shown).
     */
    public function managedTeamSize(): int
    {
        return User::query()
            ->whereKeyNot($this->user->getKey())
            ->with('staffProfile')
            ->get()
            ->filter(fn (User $candidate): bool => $candidate->canManageDutiesOf($this->user))
            ->count();
    }

    /**
     * Whether the viewer holds any duty that authorises duty assignment.
     */
    public function canAssignDuties(): bool
    {
        return $this->user->isDutyLead() || $this->user->hasDuty(DutyCatalog::ACCOUNT_MANAGE);
    }
}
