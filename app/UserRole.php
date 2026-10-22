<?php

namespace App;

enum UserRole: string
{
    case Staff = 'staff';
    case HrRegistryAdmin = 'hr_registry_admin';
    case BursaryAdmin = 'bursary_admin';
    case HeadOfDepartment = 'head_of_department';
    case Director = 'director';
    case IctSuperadmin = 'ict_superadmin';
    case InstitutionalSuperadmin = 'institutional_superadmin';
    case AuditorReadonly = 'auditor_readonly';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::HrRegistryAdmin => 'HR / Registry Administrator',
            self::BursaryAdmin => 'Bursary Administrator',
            self::HeadOfDepartment => 'Head of Department / Supervisor',
            self::Director => 'Director / Authorised Approver',
            self::IctSuperadmin => 'ICT Superadmin',
            self::InstitutionalSuperadmin => 'Institutional Superadmin',
            self::AuditorReadonly => 'Auditor / Read-only Officer',
        };
    }

    public function demoStaffNumber(): string
    {
        return match ($this) {
            self::Staff => 'NFLV-0001',
            self::HrRegistryAdmin => 'NFLV-0002',
            self::BursaryAdmin => 'NFLV-0003',
            self::HeadOfDepartment => 'NFLV-0004',
            self::Director => 'NFLV-0005',
            self::IctSuperadmin => 'NFLV-0006',
            self::InstitutionalSuperadmin => 'NFLV-0007',
            self::AuditorReadonly => 'NFLV-0008',
        };
    }

    public function demoEmail(): string
    {
        return 'demo.'.str_replace('_', '.', $this->value).'@nfvl.test';
    }

    public function demoName(): string
    {
        return $this === self::Staff ? 'Demo Staff Member' : 'Demo '.$this->label();
    }

    public function dashboardTitle(): string
    {
        return match ($this) {
            self::Staff => 'STAFF DASHBOARD',
            self::HrRegistryAdmin => 'HR / REGISTRY DASHBOARD',
            self::BursaryAdmin => 'BURSARY DASHBOARD',
            self::HeadOfDepartment => 'DEPARTMENT HEAD DASHBOARD',
            self::Director => 'APPROVER DASHBOARD',
            self::IctSuperadmin => 'ICT ADMIN DASHBOARD',
            self::InstitutionalSuperadmin => 'INSTITUTIONAL OVERSIGHT',
            self::AuditorReadonly => 'AUDIT VIEW',
        };
    }

    /**
     * @return array<int, array{category: string, title: string, description: string, status: string, route_name?: string}>
     */
    public function dashboardServices(): array
    {
        return match ($this) {
            self::Staff => [
                ['category' => 'CORRESPONDENCE', 'title' => 'Memos and circulars', 'description' => 'Targeted official communication for your account.', 'status' => 'READY', 'route_name' => 'memos.index'],
                ['category' => 'PERSONNEL', 'title' => 'Staff profile', 'description' => 'Your employment details and profile-change requests.', 'status' => 'READY', 'route_name' => 'profile.show'],
                ['category' => 'DOCUMENTS', 'title' => 'Payslips and letters', 'description' => 'Private access to your published staff documents.', 'status' => 'READY', 'route_name' => 'payslips.index'],
                ['category' => 'LEAVE', 'title' => 'Leave applications', 'description' => 'Applications, approval progress and policy-based balances.', 'status' => 'READY', 'route_name' => 'leave.index'],
                ['category' => 'SERVICE DESK', 'title' => 'Service requests', 'description' => 'Direct enquiries to Registry, Bursary or ICT.', 'status' => 'READY', 'route_name' => 'service-requests.index'],
            ],
            self::HrRegistryAdmin => [
                ['category' => 'PERSONNEL', 'title' => 'Staff directory', 'description' => 'Institutional personnel records and controlled import preview.', 'status' => 'READY', 'route_name' => 'registry.personnel.index'],
                ['category' => 'IMPORT', 'title' => 'Personnel import', 'description' => 'Validate a CSV preview before committing personnel records.', 'status' => 'READY', 'route_name' => 'registry.personnel-imports.create'],
                ['category' => 'PROFILE REVIEW', 'title' => 'Change requests', 'description' => 'Prepare proposed updates for Head of HR review before official records change.', 'status' => 'READY', 'route_name' => 'registry.profile-change-requests.index'],
                ['category' => 'WORK', 'title' => 'Duty assignments', 'description' => 'Assign, track and complete the work of Registry officers.', 'status' => 'READY', 'route_name' => 'assignments.index'],
                ['category' => 'CORRESPONDENCE', 'title' => 'Registry correspondence', 'description' => 'Draft, route, approve and issue official correspondence.', 'status' => 'READY', 'route_name' => 'registry.correspondence.index'],
                ['category' => 'ORGANISATION', 'title' => 'Organisation structure', 'description' => 'Configure units, reporting lines and delegations.', 'status' => 'READY', 'route_name' => 'registry.organisation.index'],
                ['category' => 'LEAVE', 'title' => 'Verification queue', 'description' => 'Verify assigned leave applications against configured policy.', 'status' => 'READY', 'route_name' => 'approvals.leave.index'],
                ['category' => 'SERVICE DESK', 'title' => 'Registry requests', 'description' => 'Staff enquiries, attestations, and service records.', 'status' => 'READY', 'route_name' => 'registry.service-requests.index'],
                ['category' => 'REPORTING', 'title' => 'Operational reports', 'description' => 'Personnel summaries, leave data and export audits.', 'status' => 'READY', 'route_name' => 'reports.index'],
            ],
            self::BursaryAdmin => [
                ['category' => 'TEAM', 'title' => 'Team duties', 'description' => 'Assign and withdraw duties for officers in your department and unit.', 'status' => 'READY', 'route_name' => 'ict.team-duties.index'],
                ['category' => 'PAYROLL DOCUMENTS', 'title' => 'Payslip batches', 'description' => 'Stage and validate published payroll documents.', 'status' => 'READY', 'route_name' => 'bursary.payslips.index'],
                ['category' => 'RECONCILIATION', 'title' => 'Batch review', 'description' => 'Review file matching and import exceptions.', 'status' => 'READY', 'route_name' => 'bursary.payslips.index'],
                ['category' => 'ENQUIRIES', 'title' => 'Salary enquiries', 'description' => 'Confidential salary, tax, and deduction enquiries.', 'status' => 'READY', 'route_name' => 'bursary.service-requests.index'],
                ['category' => 'REPORTING', 'title' => 'Bursary reports', 'description' => 'Payroll batch reconciliation and audit metrics.', 'status' => 'READY', 'route_name' => 'reports.index'],
            ],
            self::HeadOfDepartment => [
                ['category' => 'TEAM', 'title' => 'Team duties', 'description' => 'Assign and withdraw duties for officers in your department and unit.', 'status' => 'READY', 'route_name' => 'ict.team-duties.index'],
                ['category' => 'LEAVE', 'title' => 'Department requests', 'description' => 'Recommend or return assigned applications.', 'status' => 'READY', 'route_name' => 'approvals.leave.index'],
                ['category' => 'REPORTING', 'title' => 'Department overview', 'description' => 'View operational information within department scope.', 'status' => 'READY', 'route_name' => 'reports.index'],
            ],
            self::Director => [
                ['category' => 'WORKFLOW', 'title' => 'Approval queue', 'description' => 'Decide only on cases assigned to the current workflow stage.', 'status' => 'READY', 'route_name' => 'approvals.leave.index'],
                ['category' => 'CORRESPONDENCE', 'title' => 'Registry correspondence', 'description' => 'Decide reserved Registry matters routed to the Director/CEO.', 'status' => 'READY', 'route_name' => 'registry.correspondence.index'],
                ['category' => 'CORRESPONDENCE', 'title' => 'Memo review', 'description' => 'Review communications routed under approved policy.', 'status' => 'IN DEVELOPMENT'],
                ['category' => 'LETTERS', 'title' => 'Official letters', 'description' => 'Issue only after recorded approval and configured authority.', 'status' => 'READY', 'route_name' => 'approvals.leave.index'],
                ['category' => 'REPORTING', 'title' => 'Institutional reports', 'description' => 'Overall institutional personnel, leave, and desk statistics.', 'status' => 'READY', 'route_name' => 'reports.index'],
            ],
            self::IctSuperadmin => [
                ['category' => 'TEAM', 'title' => 'Team duties', 'description' => 'Assign and withdraw duties for officers in your department and unit.', 'status' => 'READY', 'route_name' => 'ict.team-duties.index'],
                ['category' => 'ACCOUNTS', 'title' => 'Account operations', 'description' => 'Support account lifecycle and sign-in operations.', 'status' => 'READY', 'route_name' => 'ict.accounts.index'],
                ['category' => 'SERVICE DESK', 'title' => 'ICT helpdesk', 'description' => 'Resolve technical tickets, portal access, and connectivity issues.', 'status' => 'READY', 'route_name' => 'ict.service-requests.index'],
                ['category' => 'OPERATIONS', 'title' => 'Security events', 'description' => 'Review technical audit information without private documents.', 'status' => 'READY', 'route_name' => 'audit.index'],
            ],
            self::InstitutionalSuperadmin => [
                ['category' => 'TEAM', 'title' => 'Team duties', 'description' => 'Assign and withdraw duties for officers in your department and unit.', 'status' => 'READY', 'route_name' => 'ict.team-duties.index'],
                ['category' => 'GOVERNANCE', 'title' => 'Role oversight', 'description' => 'Review authorized access assignments with an audit trail.', 'status' => 'READY', 'route_name' => 'ict.accounts.index'],
                ['category' => 'POLICY', 'title' => 'Institutional settings', 'description' => 'Oversee approved institutional policy configuration.', 'status' => 'IN DEVELOPMENT'],
                ['category' => 'REPORTING', 'title' => 'Scoped reports', 'description' => 'View governance reports within explicitly granted scope.', 'status' => 'READY', 'route_name' => 'reports.index'],
            ],
            self::AuditorReadonly => [
                ['category' => 'AUDIT', 'title' => 'Audit records', 'description' => 'Review specifically authorized audit information.', 'status' => 'READY', 'route_name' => 'audit.index'],
                ['category' => 'REPORTING', 'title' => 'Read-only reports', 'description' => 'Review approved reports without changing business records.', 'status' => 'READY', 'route_name' => 'reports.index'],
                ['category' => 'ACCESS', 'title' => 'Authorized evidence', 'description' => 'Access remains limited to explicitly granted records.', 'status' => 'IN DEVELOPMENT'],
            ],
        };
    }
}
