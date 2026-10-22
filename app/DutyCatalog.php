<?php

namespace App;

use App\Models\Duty;

/**
 * Canonical catalogue of staff duties (individually-assigned "desks").
 *
 * A user's *role* establishes the broad unit they belong to and which dashboard
 * they receive; a *duty* expresses the specific assignment an officer actually
 * performs. Authorisation is enforced through duties so that two officers in
 * the same department (for example Bursary) can hold genuinely different
 * responsibilities (Master Prompt §2.1/§E: actions and scopes, not role names).
 *
 * When a user's role changes, their duties are reset to the baseline set for
 * the new role. Baselines reproduce the previous role-based behaviour so that
 * existing accounts keep working until duties are individually refined.
 */
final class DutyCatalog
{
    public const string REGISTRY_PERSONNEL_RECORDS = 'registry.personnel_records';

    public const string REGISTRY_PROFILE_CHANGE_REVIEW = 'registry.profile_change_review';

    public const string REGISTRY_LETTERS_ATTESTATION = 'registry.letters_attestation';

    public const string REGISTRY_SERVICE_DESK = 'registry.service_desk';

    /**
     * Registry chain of command (OD-20).
     *
     * Routine Registry work rises officer → Head of HR → Registrar. The
     * Registrar is the head of the Registry department and gives final approval
     * for Registry matters; only matters the institution reserves to the
     * Director are escalated past the Registrar.
     */
    public const string REGISTRY_HEAD_REVIEW = 'registry.head_review';

    public const string REGISTRY_REGISTRAR_APPROVE = 'registry.registrar_approve';

    public const string REGISTRY_DIRECTOR_ESCALATION = 'registry.director_escalation';

    public const string BURSARY_PAYSLIP_UPLOAD = 'bursary.payslip_upload';

    public const string BURSARY_PAYSLIP_RELEASE = 'bursary.payslip_release';

    public const string BURSARY_SALARY_ENQUIRY = 'bursary.salary_enquiry';

    public const string BURSARY_RECONCILIATION = 'bursary.reconciliation';

    public const string BURSARY_SERVICE_DESK = 'bursary.service_desk';

    public const string ICT_SERVICE_DESK = 'ict.service_desk';

    public const string MEMO_AUTHOR = 'memo.author';

    public const string LEAVE_REVIEW = 'leave.review';

    public const string LEAVE_VERIFY = 'leave.verify';

    public const string LEAVE_DECIDE = 'leave.decide';

    public const string LETTER_ISSUE = 'letter.issue';

    public const string REPORTS_VIEW = 'reports.view';

    /**
     * Duty leads may assign/withdraw duties for colleagues in their own
     * department and unit (OD-19). It must never be treated as an
     * account-wide administrative duty.
     */
    public const string DUTY_LEAD = 'duty.lead';

    public const string ACCOUNT_MANAGE = 'account.manage';

    public const string AUDIT_VIEW = 'audit.view';

    /**
     * Duties that carry account-wide authority. Only ICT / Institutional
     * superadmins may grant these; a unit lead may never escalate.
     *
     * @var list<string>
     */
    public const ADMINISTRATIVE_DUTIES = [
        self::ACCOUNT_MANAGE,
        self::AUDIT_VIEW,
        self::DUTY_LEAD,
    ];

    /**
     * Duties that delegate duty-assignment authority over their unit, without
     * granting account-wide administrative power (OD-20).
     *
     * A holder of one of these may assign and withdraw duties for colleagues in
     * the Registry unit, exactly as a `duty.lead` does, but cannot reach into
     * other units and cannot grant administrative duties.
     *
     * @var list<string>
     */
    public const DELEGATED_LEAD_DUTIES = [
        self::REGISTRY_HEAD_REVIEW,
        self::REGISTRY_REGISTRAR_APPROVE,
    ];

    /**
     * The full catalogue, keyed by duty code.
     *
     * @return array<string, array{label: string, unit: string, description: string}>
     */
    public static function all(): array
    {
        return [
            self::REGISTRY_PERSONNEL_RECORDS => [
                'label' => 'Registry: personnel records',
                'unit' => 'registry',
                'description' => 'Create and correct institutional personnel records and run controlled imports.',
            ],
            self::REGISTRY_PROFILE_CHANGE_REVIEW => [
                'label' => 'Registry: profile change review',
                'unit' => 'registry',
                'description' => 'Review and approve or reject sensitive profile-change requests.',
            ],
            self::REGISTRY_LETTERS_ATTESTATION => [
                'label' => 'Registry: letters and attestations',
                'unit' => 'registry',
                'description' => 'Compose and issue attestation and service letters within delegated authority.',
            ],
            self::REGISTRY_SERVICE_DESK => [
                'label' => 'Registry: service desk',
                'unit' => 'registry',
                'description' => 'Triage, assign, respond to and close registry service requests.',
            ],
            self::REGISTRY_HEAD_REVIEW => [
                'label' => 'Registry: Head of HR review',
                'unit' => 'registry',
                'description' => 'First-line review of Registry work before it reaches the Registrar, and assignment of duties to Registry officers.',
            ],
            self::REGISTRY_REGISTRAR_APPROVE => [
                'label' => 'Registry: Registrar approval',
                'unit' => 'registry',
                'description' => 'Final Registry approval for routine matters. The Registrar heads the Registry department and may assign duties within it.',
            ],
            self::REGISTRY_DIRECTOR_ESCALATION => [
                'label' => 'Registry: escalate to Director',
                'unit' => 'registry',
                'description' => 'Advance reserved Registry matters to the Director for institutional sign-off.',
            ],
            self::BURSARY_PAYSLIP_UPLOAD => [
                'label' => 'Bursary: payslip batch upload',
                'unit' => 'bursary',
                'description' => 'Import and validate payslip batches for release.',
            ],
            self::BURSARY_PAYSLIP_RELEASE => [
                'label' => 'Bursary: payslip release',
                'unit' => 'bursary',
                'description' => 'Approve and release validated payslip batches. Separation of duties: the release officer must be a different person from the batch uploader.',
            ],
            self::BURSARY_SALARY_ENQUIRY => [
                'label' => 'Bursary: salary enquiries',
                'unit' => 'bursary',
                'description' => 'Handle confidential salary, tax and deduction enquiries under an explicit grant.',
            ],
            self::BURSARY_RECONCILIATION => [
                'label' => 'Bursary: reconciliation',
                'unit' => 'bursary',
                'description' => 'Review batch reconciliation, exception and mismatch reports.',
            ],
            self::BURSARY_SERVICE_DESK => [
                'label' => 'Bursary: service desk',
                'unit' => 'bursary',
                'description' => 'Triage, assign, respond to and close bursary service requests.',
            ],
            self::ICT_SERVICE_DESK => [
                'label' => 'ICT: service desk',
                'unit' => 'ict',
                'description' => 'Resolve technical, access and connectivity service requests.',
            ],
            self::MEMO_AUTHOR => [
                'label' => 'Correspondence: author memos',
                'unit' => 'corporate',
                'description' => 'Draft, publish and withdraw memos within the approved distribution policy.',
            ],
            self::LEAVE_REVIEW => [
                'label' => 'Leave: recommend (supervisor)',
                'unit' => 'leave',
                'description' => 'Recommend or return assigned applications at the departmental supervisor stage.',
            ],
            self::LEAVE_VERIFY => [
                'label' => 'Leave: verify (HR)',
                'unit' => 'leave',
                'description' => 'Verify leave applications at the HR verification stage.',
            ],
            self::LEAVE_DECIDE => [
                'label' => 'Leave: decide (director)',
                'unit' => 'leave',
                'description' => 'Issue final approval or rejection decisions and trigger official letters.',
            ],
            self::LETTER_ISSUE => [
                'label' => 'Letters: issue official letters',
                'unit' => 'corporate',
                'description' => 'Issue official letters within delegated authority after recorded approval.',
            ],
            self::REPORTS_VIEW => [
                'label' => 'Reports: view and export',
                'unit' => 'corporate',
                'description' => 'View and export scoped reports. Exports are audited.',
            ],
            self::ACCOUNT_MANAGE => [
                'label' => 'Accounts: lifecycle and assignments',
                'unit' => 'ict',
                'description' => 'Activate, suspend, deactivate accounts and manage role and duty assignments across the whole institution.',
            ],
            self::DUTY_LEAD => [
                'label' => 'Duty lead (assign duties in own unit)',
                'unit' => 'ict',
                'description' => 'Assign and withdraw duties for colleagues in the same department and unit. Cannot grant account-wide administrative duties.',
            ],
            self::AUDIT_VIEW => [
                'label' => 'Audit: view trail',
                'unit' => 'audit',
                'description' => 'Review the append-only audit trail without access to private document contents.',
            ],
        ];
    }

    /**
     * Baseline duties implied by a role. Resetting a role restores this set.
     *
     * @return list<string>
     */
    public static function baselineFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::Staff => [],
            UserRole::HrRegistryAdmin => [
                self::REGISTRY_PERSONNEL_RECORDS,
                self::REGISTRY_PROFILE_CHANGE_REVIEW,
                self::LEAVE_VERIFY,
                self::REGISTRY_LETTERS_ATTESTATION,
                self::REGISTRY_SERVICE_DESK,
                self::MEMO_AUTHOR,
                self::REPORTS_VIEW,
            ],
            UserRole::BursaryAdmin => [
                self::BURSARY_PAYSLIP_UPLOAD,
                self::BURSARY_SALARY_ENQUIRY,
                self::BURSARY_RECONCILIATION,
                self::BURSARY_SERVICE_DESK,
                self::REPORTS_VIEW,
                self::DUTY_LEAD,
            ],
            UserRole::HeadOfDepartment => [
                self::LEAVE_REVIEW,
                self::MEMO_AUTHOR,
                self::REPORTS_VIEW,
                self::DUTY_LEAD,
            ],
            UserRole::Director => [
                self::LEAVE_DECIDE,
                self::LETTER_ISSUE,
                self::MEMO_AUTHOR,
                self::REPORTS_VIEW,
            ],
            UserRole::IctSuperadmin => [
                self::ICT_SERVICE_DESK,
                self::ACCOUNT_MANAGE,
                self::AUDIT_VIEW,
                self::DUTY_LEAD,
            ],
            UserRole::InstitutionalSuperadmin => [
                self::REGISTRY_SERVICE_DESK,
                self::BURSARY_SERVICE_DESK,
                self::ICT_SERVICE_DESK,
                self::MEMO_AUTHOR,
                self::REPORTS_VIEW,
                self::ACCOUNT_MANAGE,
                self::AUDIT_VIEW,
                self::DUTY_LEAD,
            ],
            UserRole::AuditorReadonly => [
                self::AUDIT_VIEW,
                self::REPORTS_VIEW,
            ],
        };
    }

    /**
     * @return array{label: string, unit: string, description: string}
     */
    public static function definition(string $code): array
    {
        return self::all()[$code] ?? [
            'label' => $code,
            'unit' => 'general',
            'description' => 'Custom duty.',
        ];
    }

    /**
     * Resolve a duty code to a persisted record, creating it when missing so
     * the catalogue stays self-healing in tests and fresh environments.
     */
    public static function ensureDuty(string $code): Duty
    {
        return Duty::query()->firstOrCreate(
            ['code' => $code],
            self::definition($code),
        );
    }

    /**
     * Ensure every catalogue entry exists as a persisted, active duty.
     */
    public static function sync(): void
    {
        foreach (self::all() as $code => $definition) {
            Duty::query()->updateOrCreate(['code' => $code], $definition + ['is_active' => true]);
        }
    }
}
