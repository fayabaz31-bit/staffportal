# Security Model — NfLV Staff Self-Service Portal

This document describes the controls actually implemented in the codebase. It is an
engineering description, **not** a claim of formal compliance; Nigerian Data Protection Act
2023 review and retention schedules remain open (OD-13, OD-12).

## 1. Authentication

- **Identifiers.** Staff sign in with their staff number or institutional email
  (`AuthenticatedSessionController`). Emails are normalised to lower case; staff numbers to
  upper case.
- **Account status.** The credential attempt always includes `status = active`. Suspended or
  deactivated accounts fail authentication but retain all historical records.
- **Password storage.** Hashed via Laravel's `hashed` cast (bcrypt/argon). Plain-text passwords
  are never stored; a temporary password is shown once from session flash and never persisted.
- **Brute-force protection.** A named `login` rate limiter (`AppServiceProvider`) allows
  5 attempts per minute per `identifier|IP`. Covered by
  `AuthenticationTest::test_repeated_failed_logins_are_rate_limited`.
- **Session.** `SESSION_DRIVER=database`; the session is regenerated on login and invalidated on
  logout. `SESSION_ENCRYPT` is configurable and should be enabled for production.

## 2. Password recovery

- `PasswordResetLinkController` returns a **generic** response for both known and unknown
  emails, preventing account enumeration (covered by
  `PasswordResetTest::test_requesting_a_reset_link_does_not_reveal_whether_an_account_exists`).
- Reset is throttled (`throttle:6,1`), token-based and single-use via Laravel's password broker.
- A successful reset is audited as `account.password_reset` and rotates the remember token.

## 3. Authorisation

- **Two-layer model.** A user's `role` sets the broad unit and dashboard; **duties** ("desks") are
  the specific assignments that authorise actions. Gates check duties via `User::hasDuty()`, not
  role names, so two officers in the same unit can hold genuinely different responsibilities.
- **Baselines + overrides.** Duties are reset to the role baseline on account creation and on any
  role change (`App\Observers\UserObserver` → `User::syncRoleBaselineDuties()`), then refined per
  officer in the ICT account console. A role change is effective on the very next request (AC-10),
  for both routes and duties.
- **Gate-based, evaluated per request.** Every route under a sensitive prefix carries `can:<gate>`,
  and controllers call `Gate::authorize(...)` again for state changes.
- **Gates** (all require `status === 'active'`):

  | Gate | Required duty |
  |---|---|
  | `review-profile-change-requests` | `registry.profile_change_review` |
  | `manage-personnel-records` | `registry.personnel_records` |
  | `manage-memos` | `memo.author` |
  | `manage-payslips` | `bursary.payslip_upload` **or** `bursary.payslip_release` |
  | `release-payslips` | `bursary.payslip_release` |
  | `access-leave-approvals` | `leave.review`, `leave.verify` **or** `leave.decide` |
  | `manage-registry-requests` | `registry.service_desk` |
  | `manage-bursary-requests` | `bursary.service_desk` |
  | `manage-ict-requests` | `ict.service_desk` |
  | `manage-correspondence` | `registry.personnel_records`, `registry.letters_attestation`, `registry.head_review`, `registry.registrar_approve`, `letter.issue` **or** `memo.author` |
  | `issue-correspondence` | `registry.letters_attestation`, `letter.issue` **or** `registry.registrar_approve` |
  | `manage-organisation` | `account.manage`, `registry.registrar_approve` **or** `duty.lead` |
  | `view-assignments` / `assign-duties` | any active account; the controller re-checks record/unit scope |
  | `manage-accounts` | `account.manage` |
  | `manage-duties` | per target account: `account.manage`, or `duty.lead` for a colleague in the same department (and unit, where the lead's role maps to one) |
  | `view-audit-log` | `audit.view` |
  | `view-reports` | `reports.view` |
  | `view-payslip` | owner only, and only when published |
  | `view-official-letter` | owner, or `registry.letters_attestation` / `letter.issue` |
  | `view-service-request` | participants/unit handlers via `ServiceRequest::canAccess()` |

  Role → baseline duty mappings are documented in `docs/ROLE_PERMISSION_MATRIX.md`; the catalogue
  lives in `App\DutyCatalog`.
- **Self-protection.** An administrator cannot suspend/deactivate their own account or change
  their own role; only an institutional superadmin may grant the institutional superadmin role.
  Duty grants/withdrawals are audited (`account.duty_granted` / `account.duty_revoked`).
- **Separation of duties (OD-19, mandatory).** `bursary.payslip_release` must be held by a
  different officer from `bursary.payslip_upload`. The release action refuses a batch the actor
  uploaded, regardless of duties; and the assignment action refuses to grant the release duty to
  an account that holds the upload duty. Both are enforced in code and covered by tests.
- **Unit-lead scoping.** A `duty.lead` may manage duties only for a colleague sharing the same
  department (and the same unit, where the lead's role maps to a service unit); they cannot act on
  their own account and cannot grant account-wide administrative duties (`account.manage`,
  `audit.view`, `duty.lead`). The lead reaches this through **Dashboard → Team duties**
  (`ict.team-duties.index`), which lists only the colleagues they may manage.
- **Anti-IDOR.** Cross-user resource requests return 404/403 rather than leaking existence
  (payslips, letters, notifications, service requests).

### 3.1 Registry workflow authority

- **Stage authority comes from data.** The current `workflow_steps` row names the duty required
  to act. `WorkflowEngine` re-reads the step and the actor's effective duties on every decision;
  hiding a button is never the control.
- **Versions are pinned.** A `workflow_instances` row stores the `workflow_version_id` it started
  on. Publishing a new version changes future documents only; in-flight work is never silently
  re-routed.
- **Self-approval.** The engine refuses a decision by the originator, and refuses a second
  decision by the same officer at the same stage.
- **Completion ≠ approval ≠ issuance.** `work_assignments` record task completion; correspondence
  records approval (`approved_by`/`approved_at`), then issuance (`issued_by`/`issued_at`), then
  delivery status. Only an approved document may be issued, and an issued document cannot be
  edited.
- **Record-level correspondence access.** Visible only to the originator, the named recipient,
  officers participating in that document's workflow, or holders of an explicit Registry
  correspondence duty. `ict_superadmin` holds none of those and is denied (negative-tested).
- **Attachments.** Uploaded only by the originator while the document is editable. The allow-list
  is PDF, JPG/JPEG, PNG and DOC/DOCX up to 10 MB; files are stored on the `private` disk with a
  SHA-256 hash. Every upload and download appends an audit row, and a download is refused unless
  the attachment actually belongs to the document in the URL (IDOR guard).
- **Assigned work.** `WorkAssignmentController` scopes lists and actions to assignments the actor
  created, received, or supervises; only the supervisor can complete or return submitted work.
- **Organisation configuration.** Unit codes are normalised; reporting lines reject self-reference,
  cycles and overlapping open line-management records; delegations are time-bounded and revocable.
  Every change is audited.

## 4. Data protection and privacy

- Private documents live on the `private` disk and are streamed after authorisation with
  `no-store` cache headers — never served from the webroot.
- Confidentiality is preserved between units: Registry cannot read Bursary cases and vice versa
  (`test_cross_unit_isolation_*`). Salary/banking enquiries are auto-classified confidential.
- **No sensitive payloads by email.** `PortalEmailNotification` contains only a generic prompt to
  sign in; no salary, memo body, leave reason or document reference is emailed.
- **Open-redirect defence.** Notification deep links are followed only when they are relative
  paths or absolute URLs on this application's own origin (`NotificationController`).

## 5. Audit trail

- `audit_logs` is append-only. `AuditLog::save()` throws for existing rows and `AuditLog::delete()`
  always throws; the table has no `updated_at` (covered by
  `AuditTrailTest::test_audit_entries_are_append_only`).
- Material events recorded include account status/role changes, temporary-password issuance,
  password resets, memo publish/withdraw, payslip publish/withdraw, leave submit/decision and
  official-letter issuance, service-request lifecycle, private document downloads, duty
  grant/withdrawal, work-assignment lifecycle, correspondence version/submit/decision/issue,
  correspondence attachment upload/download, and organisation unit/reporting/delegation changes.
- Context deliberately excludes passwords, tokens and salary values.

## 6. Input, upload and transport hardening

- All requests are validated server-side; free-text fields are length-bounded.
- ZIP batches are extracted with path-traversal, symlink and size protections
  (`BursaryPayslipBatchController::extractZipSafely`); non-PDF entries are quarantined.
- Blade auto-escaping is used throughout; the audit viewer JSON-encodes context before display.
- CSRF protection is enabled on all state-changing forms.

## 7. Explicitly out of scope / not yet implemented

- **MFA** for privileged roles (OD-15) — not implemented.
- **SSO** (OD-17) — deferred.
- **Encryption at rest** beyond MySQL/storage defaults; `SESSION_ENCRYPT`, DB encryption and
  key management are deployment concerns.
- **Malware scanning** of uploads — type/size/path validation only.
- **Impersonation** — intentionally never implemented.

## 8. Reporting a vulnerability

Report suspected issues to the ICT contact for the portal. Do not include real staff data in any
report; use synthetic examples.
