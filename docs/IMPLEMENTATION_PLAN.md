# Implementation Plan — NfLV Staff Self-Service Portal

Master prompt: `NfLV_Staff_Portal_AI_Implementation_Master_Prompt.md`. Requirements source of truth: PRD v1.0 (13 Oct 2026).
Stack (as actually installed — verified from `composer.json`/`package.json` and the database, 19 Oct 2026): **Laravel 13** (`laravel/framework: ^13.17`, PHP `^8.3` — local PHP 8.3.33), **Blade views + Tailwind CSS 4 + Vite 8** (`laravel-vite-plugin` 3), **PHPUnit 12** (`phpunit/phpunit: ^12.5` — **not** Pest), **barryvdh/laravel-dompdf 3** for PDF letters, **MySQL 8.4.3** (Laragon local dev, selected by user on 15 Oct 2026), database queue (`QUEUE_CONNECTION=database`), `log` mailer in development (`MAIL_MAILER=log`).

> Correction (19 Oct 2026): an earlier revision of this plan described Livewire 4, Breeze 2.4, Spatie Laravel Permission 8 and Pest 5. **None of those packages are installed.** Authentication is hand-rolled against `AuthenticatedSessionController`; authorisation uses native PHP 8.3 enum roles (`App\UserRole`) plus Laravel Gates defined in `AppServiceProvider`. This section is corrected to match the repository.

## Phases (Master Prompt §G)

| Phase | Scope | Exit gate |
|---|---|---|
| 0 | Discovery, environment report, requirements traceability, decisions list, test conventions | Environment confirmed, blockers recorded |
| 1 | Foundation & UI: MySQL, design tokens/layouts, landing page, auth (staff-ID/email login, reset, rate limits, MFA hooks), roles/policies, staff dashboard shells, migrations/factories/DEMO seeders, baseline tests | Every role signs in to a scoped dashboard; route+record permission tests pass |
| 2 | Staff directory, CSV/XLSX import (dry-run/preview/commit), profile change requests, lifecycle | Self vs admin access and import failure tests pass |
| 3 | Memos: editor, recipient targeting, classification, approval states, attachments, inbox/acknowledgement, notifications | Cross-department access prevention + publishing workflow tests pass |
| 4 | Payslips: staging, manifest mapping, validation, quarantine, release approval, private publication, replacements | Malformed batch never publishes; cross-user download denied; retries idempotent |
| 5 | Leave + configurable approval engine + balance ledger + official letters (PDF) | Allowed workflows & concurrency tests pass; UNCONFIGURED policies block final actions |
| 6 | Service requests, reports, scoped exports | Status history, privacy and notification tests pass |
| 7 | Hardening: security review, permission-matrix audit, performance, restore drill, a11y, docs | AC-01…AC-15 mapped to executable evidence |

## Module map (modular monolith, as actually laid out)

```
app/
  Http/Controllers/         request handlers: director, dashboard, staff self-service,
                            service desks, approvals, reports
  Http/Controllers/Auth/    AuthenticatedSessionController, PasswordResetLinkController,
                            NewPasswordController
  Models/                   Eloquent models (User, StaffProfile, Department, Memo,
                            MemoRecipient, PayslipBatch, Payslip, LeaveType,
                            LeaveEntitlement, LeaveApplication, LeaveApprovalAction,
                            OfficialLetter, ServiceRequest + messages/status logs,
                            ProfileChangeRequest, PersonnelImportBatch, ReportExportAudit,
                            AuditLog, ReferenceSequence)
  Notifications/            PortalInboxNotification (database, sync),
                            PortalEmailNotification (mail, queued), PortalNotifier
  Providers/AppServiceProvider.php   Gates + the `login` rate limiter
  UserRole.php              role enum, labels, demo identities and dashboard tiles
resources/views/  layouts/ partials/ landing/ auth/ memos/ payslips/ leave/ letters/
                  profile/ registry/ bursary/ ict/ audit/ service-requests/ reports/
                  notifications/ approvals/
database/migrations/ factories/ seeders/
tests/Feature/ tests/Unit/   docs/
```

> Note: the master prompt's illustrative `app/Domain/...` tree was not adopted. The
> implementation follows standard Laravel `app/Http/Controllers` + `app/Models`
> conventions (per AGENTS.md: "Stick to existing directory structure").

## Initial ERD (Phase 1 tables in **bold**; later phases listed for traceability)

- **`users`** — id, `staff_number` (unique, indexed), email (nullable/unique), `password_hash`, `status` (active/suspended/deactivated), `mfa_*`, `last_login_at`, timestamps. Login accepts staff number *or* email (AUTH-01).
- **`departments`** — code (unique), name, active.
- **`staff_profiles`** — user_id FK, legal/preferred names, department_id FK, designation, employment status, grade, contact fields split into *self-editable* vs *official* columns; official fields writable only via approval (PROF-03/04).
- **`roles` …** — roles are the native `users.role` string enum (`App\UserRole`), enforced through Laravel Gates. No Spatie permission tables are installed.
- **`audit_logs`** — actor_id, action, subject_type/subject_id, context JSON (no secrets/salary payloads), created_at; append-only by policy.
- **`notifications`** (Laravel), **`jobs`**, **`failed_jobs`**, `sessions`, `password_reset_tokens`.
- Later: `staff_profile_change_requests`, `memos`, `memo_recipients`, `memo_attachments`, `documents`, `payslip_batches`, `payslips`, `leave_types`, `leave_entitlements`, `leave_balance_ledger`, `leave_applications`, `approval_workflow_definitions`, `approval_stage_definitions`, `approval_steps`, `letter_templates`, `issued_letters`, `service_requests`, `service_request_messages`, `system_settings`, `import_jobs`, `notification_delivery_attempts`.

Design rules enforced (PRD §6.1): unique staff number; stable ID references (no name keys); every private doc has explicit owner/recipients; approval history immutable; transaction boundaries for multi-record changes; private storage only; soft-delete vs deactivation distinction; audit log restricted and tamper-resistant.

## Testing conventions

- PHPUnit 12, feature/integration tests on an isolated database (`staffportal_test`, `RefreshDatabase`), synthetic factories only, never production data or live SMTP (`MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=array` in `phpunit.xml`).
- Test names carry the PRD ID they evidence where one exists (e.g. `test_ac01_valid_staff_login_displays_personal_dashboard`).
- Every phase adds positive tests for the feature and negative tests for IDOR/cross-role access before the phase can be reported done.
- Run the narrowest covering test file while iterating; run `php artisan test --compact` before reporting complete. Run `vendor/bin/pint --dirty` on changed PHP.

## Phase execution status

- **Phase 0 (Discovery & Environment):** Complete.
- **Phase 1 (Foundation, Identity & Role Dashboards):** Complete. Authenticated login by staff number and email, 8 role shells, landing page.
- **Phase 2 (Staff Directory & Personnel Profiles):** Complete. Self-service contact updates, sensitive profile-change approval workflow, CSV dry-run import preview & commit, manual staff creation.
- **Phase 3 (Memos & Correspondence):** Complete. Authoring, targeting by individual/dept/institution, recipient snapshotting, immutability, withdrawal, and delivery/view/acknowledgement tracking.
- **Phase 4 (Payslip Distribution & Reconciliation):** Complete. Bursary ZIP & PDF batch upload, dry-run validation against active staff records, automatic quarantine on errors (release blocked), authorized release approval, private storage streaming with anti-IDOR/cache headers, download event audit tracking, and emergency batch withdrawal.
- **Phase 5 (Leave & Approval Engine + Official Letters):** Complete for the implemented scope. Leave types with configurable supervisor/HR flags, per-staff entitlements, business-day calculation (weekends excluded), active-leave overlap prevention, a multi-stage approval engine (HOD recommend → HR verify → Director approve/reject/return), optimistic-concurrency protection, a strict UNCONFIGURED policy block for final approvals, and Dompdf official approval letters streamed privately with anti-IDOR authorisation. **A full balance ledger of every debit/credit is deferred**, and the approval stages are not yet DB-configurable (see gaps below).
- **Phase 6 (Registry/Bursary Service Requests & Scoped Reporting):** Complete. Categorized staff service requests, immutable tracking ID (`REQ-YYYY-XXXX`), strict administrative unit routing (Registry, Bursary, ICT), automatic confidentiality isolation for salary/banking matters, internal administrative notes invisible to applicants, thread history and status lifecycle tracking (`open`, `in_progress`, `escalated`, `resolved`, `closed`), operational reports dashboard, and role-authorized CSV exports with audit logging and privacy filters preventing salary payload exposure.
- **Phase 7 (Hardening, Security Review, Verification & Documentation):** Substantially complete. A security/hardening pass fixed the missing `private` document disk, the undefined `manage-ict-requests` gate, a reference-number race, Zip-Slip exposure in payslip extraction and the institutional timezone. It added the account-lifecycle console (AC-10/AC-15), in-app + queued notifications (F7/AC-11), an append-only audit log, secure password reset, and executive dashboards for ICT/Auditor roles. Evidence matrix: `docs/ACCEPTANCE_EVIDENCE.md`. **All 197 automated tests pass (1180 assertions).**
- **Phase 8 (Registry Department Management):** Complete for the implemented scope. Seven official departments with fixed display order; department-grouped staff-detail upload preview with extended personnel fields; a separate assigned-work entity (`work_assignments`) with supervision lifecycle; a versioned, effective-dated workflow engine; configurable correspondence categories/templates, versioned drafts, unique references, approval separated from issuance, private PDFs and audited attachments; configurable units, reporting lines and time-bounded delegations; Registry desk-scoped dashboards. The chain duties are explicit grants (OD-20) and seeded workflows are editable pilot defaults.

## Honest remaining gaps (deferred, not hidden)

The phases above are functionally exercised, but the following items are **not** finished and must not be read as complete:

- **Institutional policy still open** — leave entitlement rules, final signing authority, payroll format/release procedure, workflow definitions, SMTP, retention and RPO/RTO remain `open` in `docs/OPEN_DECISIONS.md` (OD-04/05/07/09/10/12/14). Affected capabilities stay UNCONFIGURED/blocked by design.
- **AC-12 (restore drill) is not demonstrated** — only a written runbook exists.
- **AC-14 (load)** — throttling and row-locking are tested, but no dedicated load/soak test exists.
- **Email requires a queue worker** — the in-app inbox row is written synchronously, but `PortalEmailNotification` is queued (`QUEUE_CONNECTION=database`); without `php artisan queue:work` the email sits in `jobs`.
- **Feature depth still missing** — memo attachments/approval states beyond normal classification, XLSX import (CSV only), a manifest-CSV payslip adapter, a DB-configurable **leave** workflow (Registry correspondence is already data-driven and versioned), a leave balance ledger, public holidays/carry-forward, an institutional-settings console, a UI for editing correspondence workflow steps, and richer DEMO seed data for memos/payslips/leave.
- **Demo accounts** render only under `APP_ENV=local|testing`; the shared demo password `password` is not production-safe.

