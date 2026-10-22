# Role / Permission Matrix (PRD §2, §2.1, §4)

Permissions are implemented as named **abilities** checked by policies/gates on the server.
Scope column: `own` = record owner only, `assigned` = workflow-assigned cases, `dept` = own department, `institution` = all in authorised domain.
Hidden navigation is never a control — actions are server-enforced (named gates in `app/Providers/AppServiceProvider.php`, ownership checks in policies/model methods) and negatively tested in `tests/Feature` (Master Prompt §E).

> **Implementation status (honest):** this matrix is the design target from the PRD. Abilities marked `IN DEVELOPMENT` below are not yet implemented as gates/controllers and must not be assumed present. Where a row is implemented, the "Notes" column is the actual server behaviour, not aspirational.

## Roles

| Key (DB) | PRD role | Notes |
|---|---|---|
| `staff` | Staff | Default role for every account |
| `hr_registry_admin` | HR/Registry Administrator | Personnel + correspondence; **no automatic payroll access** |
| `bursary_admin` | Bursary Administrator | Payslip batches + salary enquiries; cannot change HR decisions |
| `head_of_department` | Head of Department / Supervisor | Department-scoped review; **no blanket payroll view** |
| `director` | Director / Authorised Approver | Decides only what the configured workflow routes to them |
| `ict_superadmin` | Superadmin – ICT | Accounts, settings, logs; **no approval authority, no private-document reading** |
| `institutional_superadmin` | Superadmin – Institutional | Governance-scoped, audited; **not a privacy bypass** (Master Prompt §E: no blanket bypass) |
| `auditor_readonly` | Auditor / Read-only Officer | Read-only, explicitly scoped, cannot mutate business data |

## Role vs. duty (two-layer model)

A **role** establishes the broad unit an officer belongs to and which dashboard they receive. A **duty** (an individually-assigned "desk") expresses what that officer actually does. Authorisation is enforced through **duties**, not role-name comparisons, so two officers in the same department can hold genuinely different responsibilities (PRD §2.1; Master Prompt §E: actions and scopes, not role names).

- **Effective duties = role baseline ∪ per-officer grants − withdrawals.** On account creation, and whenever a role changes, duties are reset to the baseline set for that role (`App\Observers\UserObserver` → `User::syncRoleBaselineDuties()`). ICT/Institutional superadmins then add or withdraw individual duties in the account console; every change is audited and notified.
- Baselines **reproduce the previous role-based behaviour**, so existing accounts keep working until duties are deliberately refined.

| Duty code | Unit | Grants |
|---|---|---|
| `registry.personnel_records` | registry | Create/correct personnel records, controlled imports |
| `registry.profile_change_review` | registry | Review sensitive profile-change requests |
| `registry.letters_attestation` | registry | Compose/issue attestation and service letters |
| `registry.service_desk` | registry | Handle registry service requests |
| `registry.head_review` | registry | First-line review of Registry work; Head of HR stage of the configured chain. Also delegates Registry duty assignment (OD-20) |
| `registry.registrar_approve` | registry | Final Registry approval for routine matters; Registrar heads the department and may assign Registry duties |
| `registry.director_escalation` | registry | Act on reserved Registry matters **only when a configured workflow routes them to the Director** |
| `bursary.payslip_upload` | bursary | Import and validate payslip batches |
| `bursary.payslip_release` | bursary | Approve/release validated batches (**must be a different officer from the uploader**) |
| `bursary.salary_enquiry` | bursary | Confidential salary/tax/deduction enquiries |
| `bursary.reconciliation` | bursary | Batch reconciliation and exception review |
| `bursary.service_desk` | bursary | Handle bursary service requests |
| `ict.service_desk` | ict | Resolve technical/access service requests |
| `memo.author` | corporate | Draft/publish memos |
| `leave.review` | leave | Supervisor recommendation |
| `leave.verify` | leave | HR verification |
| `leave.decide` | leave | Final decision + letter |
| `letter.issue` | corporate | Issue official letters |
| `reports.view` | corporate | View/export scoped reports (audited) |
| `account.manage` | ict | Account lifecycle and role/duty assignment (institution-wide) |
| `duty.lead` | ict | Assign/withdraw duties for colleagues in your own department (and unit where applicable) |
| `audit.view` | audit | View the audit trail |

> **Decided (OD-19, 20 Oct 2026):** ICT and Institutional superadmins (`account.manage`) may assign duties for any account. A **unit lead** (`duty.lead`, carried by each unit's administrative role and by HOD) may also assign/withdraw duties for colleagues in **their own department** — where the lead's role maps to a service unit (Registry, Bursary, ICT) the target must share that unit too. A lead can never act on their own account, and never grants account-wide administrative duties (`account.manage`, `audit.view`, `duty.lead`). The lead's entry point is **Dashboard → Team duties** (`ict.team-duties.index`), which lists the colleagues they may manage. **Separation of duties is mandatory:** `bursary.payslip_release` must be held by a different officer than `bursary.payslip_upload`; the uploader can never release their own batch and the release duty cannot be granted to an account holding the upload duty. See `docs/OPEN_DECISIONS.md`.

> **Decided (OD-20, 22 Oct 2026 — Registry chain):** Registry work rises **officer → Head of HR → Registrar**. `registry.head_review` and `registry.registrar_approve` are **explicit grants**, never role baselines, and also carry delegated duty-assignment authority inside Registry (`DutyCatalog::DELEGATED_LEAD_DUTIES`). `registry.director_escalation` is an explicit grant used only when a configured workflow routes a reserved matter to the Director. The `hr_registry_admin` baseline therefore no longer includes `duty.lead`; a Registry desk officer can only assign duties if granted one of the delegated lead duties. Seeded workflows are pilot defaults and are versioned.

> **Naming:** the `duties` table is the **authorisation catalogue** (a desk that permits actions). Assigned work is a separate entity, `work_assignments` (UI label **Duty assignment**), with its own acknowledgement, submission, supervision and completion lifecycle. The two must not be conflated.

### Registry chain of command (OD-20)

| Desk | Duty | May do | Scope |
|---|---|---|---|
| Registry officer | baseline duties (`registry.personnel_records`, `registry.profile_change_review`, `registry.letters_attestation`, `registry.service_desk`, …) | Prepare records/letters, own assigned work, draft correspondence | Own work + records in role scope |
| Head of HR | `registry.head_review` (explicit grant) | First-line review; assign ordinary Registry duties | Registry department/unit |
| Registrar | `registry.registrar_approve` (explicit grant) | Final Registry approval; issue; assign Registry duties | Registry department, institution-wide records |
| Director/CEO | `registry.director_escalation` (explicit grant) | Decide only matters routed by a configured workflow | Routed matters only |

No role baseline includes the three chain duties, so a new `hr_registry_admin` account starts as an officer until leadership deliberately grants a chain duty. Self-approval is refused by the engine even where a duty is held.

## Ability matrix

Legend: ✔ = allowed, ✖ = denied, (scope) = only within stated scope. Blank = not applicable/denied.

| Ability (permission) | staff | hr_registry | bursary | hod | director | ict_super | inst_super | auditor |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| **Dashboard / self-service** |
| `dashboard.staff.view` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| `dashboard.admin.view` (scoped) | ✖ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✖ |
| `profile.view.own` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| `profile.edit.contact.own` (self-editable fields) | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✖ |
| `profile.view.others` | ✖ | ✔ institution | ✖ | ✔ dept | ✔ dept | ✖ | ✔ authorised | ✔ authorised |
| `profile.change.approve` (sensitive-field approval) | ✖ | ✔ | ✖ | ✖ | ✔ | ✖ | ✖ | ✖ |
| `personnel.import.run` (CSV/XLSX dry-run + commit) | ✖ | ✔ | ✖ | ✖ | ✖ | ✖ | ✖ | ✖ |
| `personnel.record.create` / `.correct` | ✖ | ✔ | ✖ | ✖ | ✖ | ✖ | ✖ | ✖ |
| `personnel.record.deactivate` | ✖ | ✔ | ✖ | ✖ | ✖ | ✖ | ✖ | ✖ |
| **Correspondence (memos)** |
| `memo.draft` / `memo.publish` | ✖ | ✔ | ✖ | ✔ dept | ✔ | ✖ | ✔ | ✖ |
| `memo.approve` (classification-gated) | ✖ | ✔ (per OD-11) | ✖ | ✖ | ✔ | ✖ | ✖ | ✖ |
| `memo.view` | ✔ addressed only | ✔ authored | ✖ | ✔ addressed/own dept authored | ✔ addressed | ✖ (not entitled) | ✔ authorised scope | ✔ authorised scope |
| `memo.withdraw` (audited) | ✖ | ✔ authored | ✖ | ✔ own | ✔ | ✖ | ✔ | ✖ |
| `memo.stats.view` | ✖ | ✔ | ✖ | ✔ dept | ✔ | ✖ | ✔ | ✔ |
| **Payslips (bursary domain)** |
| `payslip.batch.upload` / `.validate` | ✖ | ✖ | ✔ | ✖ | ✖ | ✖ | ✖ | ✖ |
| `payslip.batch.approve-release` | ✖ | ✖ | ✔ authorised release officer (must differ from the uploader) | ✖ | ✖ | ✖ | ✖ | ✖ |
| `payslip.batch.withdraw-replace` (audited) | ✖ | ✖ | ✔ | ✖ | ✖ | ✖ | ✖ | ✖ |
| `payslip.view.own` / `payslip.download.own` | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✖ (no automatic payroll view) |
| `payslip.view.other` | ✖ | ✖ | ✖ (record-level: bursary sees batch ops not contents beyond duty) | ✖ | ✖ | ✖ | ✖ | ✖ |
| `payslip.reconciliation.view` | ✖ | ✖ | ✔ | ✖ | ✖ | ✖ | ✖ | ✔ |
| `salary.enquiry.respond` | ✖ | ✖ | ✔ (confidential, grant-scoped) | ✖ | ✖ | ✖ | ✖ | ✖ |
| **Leave** |
| `leave.view-entitlement.own` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |
| `leave.apply` | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✖ |
| `leave.review` (recommend/return) | ✖ | ✖ | ✖ | ✔ assigned dept | ✔ assigned | ✖ | ✖ | ✖ |
| `leave.verify` (HR verification stage) | ✖ | ✔ | ✖ | ✖ | ✖ | ✖ | ✖ | ✖ |
| `leave.decide` (approve/reject final) | ✖ | ✖ | ✖ | ✖ | ✔ (only if routed & active assignee) | ✖ | ✖ | ✖ |
| `leave.balance.manage` (entitlement config) | ✖ | ✔ (policy-gated: blocked while UNCONFIGURED) | ✖ | ✖ | ✖ | ✖ | ✖ | ✖ |
| `leave.letter.issue` | ✖ | ✖ | ✖ | ✖ | ✔ (approval must be recorded first) | ✖ | ✖ | ✖ |
| `leave.view.others.status` | ✖ | ✔ | ✖ | ✔ dept | ✔ | ✖ | ✔ authorised | ✔ authorised |
| **Official letters** |
| `letter.template.manage` (versioned) | ✖ | ✔ HR letters | ✖ | ✖ | ✔ | ✖ | ✖ | ✖ |
| `letter.issue` | ✖ | ✔ (within delegated authority) | ✖ | ✖ | ✔ | ✖ | ✖ | ✖ |
| `letter.view.own` | ✔ | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✔ authorised only |
| **Service requests** |
| `srv.request.create` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✖ |
| `srv.request.view-own` / reply thread | ✔ | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✔ own | ✖ |
| `srv.unit.handle` (assign/respond/escalate/close) | ✖ | ✔ registry cases | ✔ bursary cases (incl. confidential salary cases) | ✖ | ✖ | ✖ | ✖ | ✖ |
| **Administration / audit / settings** |
| `user.account.activate/suspend/deactivate` | ✖ | ✖ | ✖ | ✖ | ✖ | ✔ (never own account) | ✔ (never own account) | ✖ |
| `user.role.assign` (audited, immediate effect) | ✖ | ✖ | ✖ | ✖ | ✖ | ✔ except the `institutional_superadmin` role | ✔ | ✖ |
| `system.settings.manage` | ✖ | ✖ | ✖ | ✖ | ✖ | ✖ NOT BUILT | ✖ NOT BUILT | ✖ |
| `audit.log.view` | ✖ | ✖ | ✖ | ✖ | ✖ | ✔ technical scope (no sensitive payload content) | ✔ | ✔ scoped read-only |
| `report.run` / `report.export` (audited) | ✖ | ✔ personnel scope | ✔ batch recon scope | ✔ dept scope | ✔ delegated scope | ✖ (no business data export) | ✔ authorised | ✔ read-only scoped |
| **Notifications** |
| `notification.inbox.view.own` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |

### Confidentiality rules encoded in policies (PRD §2.1 + Master Prompt §E)
1. **Own-records rule:** any private artefact (profile, payslip, leave, letter) is loaded through an ownership/authorised-recipient policy — never by raw ID.
2. **Registry ≠ payroll:** `hr_registry_admin` has zero `payslip.*` abilities.
3. **ICT ≠ business authority:** `ict_superadmin` manages accounts and operations logs; cannot approve leave, publish memos, or open private documents/contents.
4. **Bursary isolation:** confidential salary enquiries are visible only with an explicit grant; no automatic cross-unit view.
5. **Approver validity:** `leave.decide` etc. require the user to be an *active, current assignee* of the workflow stage (checked with row locks to stop double decisions).
6. **Supersadmins are scoped:** `institutional_superadmin` grants are audited and permission-scoped; no blanket "bypass all" gate exists in code.
7. **Auditor:** read-only abilities only, scoped to explicitly authorised records; export actions are logged.
8. **Impersonation:** not implemented (not in MVP; Master Prompt §E).

### Implemented in code today (post-remediation)
- **Duty ("desk") assignments:** `duties` + `duty_user` tables, `App\Models\Duty`, `App\UserRole`-derived baselines in `App\DutyCatalog`, and `User::hasDuty()`. Named gates in `AppServiceProvider` authorise by duty. ICT console (`/ict/accounts`) grants/withdraws duties (audited + notified). Covered by `tests/Feature/DutyAssignmentTest.php`.
- **Registry department management:** `DepartmentCatalog` fixes the seven official departments and display order; `PersonnelDirectoryController` groups/filters by department; `PersonnelImportController` accepts optional personnel-detail columns and previews imports grouped by department. Covered by `DepartmentCatalogTest`, `PersonnelImportTest`.
- **Configurable organisation:** `OrganisationController` manages `organisational_units`, `reporting_relationships` and time-bounded `delegations` behind the `manage-organisation` gate (Registrar / account managers). Reporting cycles and overlapping line-management records are rejected; every change is audited. Covered by `OrganisationStructureTest`.
- **Assigned work (separate from duties):** `WorkAssignmentController` + `AssignmentWorkflow` implement draft → assigned → acknowledged → in progress → submitted → returned/resubmitted → completed, with supervision notes, completion evidence and notifications. Access is record/unit-scoped. Covered by `RegistryAssignmentTest`.
- **Correspondence:** `RegistryCorrespondenceController` + `CorrespondenceService` + `WorkflowEngine` implement versioned drafts, submission, stage decisions, approval, issuance and private PDF storage. `document_attachments` stores evidence on the private disk; download is audited and IDOR-protected. Covered by `RegistryCorrespondenceTest`.
- **Registry dashboards:** `DashboardMetrics` scopes the Head of HR, Registrar, Personnel Officer and Director views by the workflow stage currently assigned to the viewer; tiles link to assignments/correspondence queues. Covered by `DashboardMetricsTest`.
- **Account lifecycle (AC-15):** `manage-accounts` gate → `IctAccountController` status/role/temporary-password actions (`routes/web.php`, `can:manage-accounts`). Both `ict_superadmin` and `institutional_superadmin` hold it; a user may never suspend/deactivate their own account and may never change their own role. Only an `institutional_superadmin` may assign the `institutional_superadmin` role. Every change is written to the append-only `audit_logs` table and notified.
- **Audit viewer (AC-10/AC-15):** `view-audit-log` gate → `AuditLogController@index`. `ict_superadmin`, `institutional_superadmin` and `auditor_readonly`; read-only, no business-data mutation.
- **Notifications (F7):** `PortalNotifier` writes an in-app `PortalInboxNotification` (database) synchronously and queues a generic `PortalEmailNotification` (mail) for every account/role/status change.
- **Not implemented:** `system.settings.manage` (no settings console or gate exists); role/permission management beyond the single-role swap above; impersonation; a full per-record access-grant registry for auditor scoping.

### Account lifecycle states (AUTH-07)
`active` → may authenticate. `suspended` / `deactivated` → authentication blocked, historical records preserved, status change audited (AC-15).
