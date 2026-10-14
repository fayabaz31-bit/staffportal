# Implementation Plan — NfLV Staff Self-Service Portal

Master prompt: `NfLV_Staff_Portal_AI_Implementation_Master_Prompt.md`. Requirements source of truth: PRD v1.0 (13 Oct 2026).
Stack (verified on Packagist, 14 Oct 2026): **Laravel 13** (requires PHP ^8.3 — local PHP 8.3.33 ✔), **Livewire 4**, **Tailwind CSS 4 + Vite**, **Breeze 2.4** (Livewire stack) for hardened auth, **Spatie Laravel Permission 8** for RBAC, **Pest 5** tests, **PostgreSQL 17.6** (portable, local dev), Dompdf (Phase 5), database queues, `log` mailer in development.

## Phases (Master Prompt §G)

| Phase | Scope | Exit gate |
|---|---|---|
| 0 | Discovery, environment report, requirements traceability, decisions list, test conventions | Environment confirmed, blockers recorded |
| 1 | Foundation & UI: PostgreSQL, design tokens/layouts, landing page, auth (staff-ID/email login, reset, rate limits, MFA hooks), roles/policies, staff dashboard shells, migrations/factories/DEMO seeders, baseline tests | Every role signs in to a scoped dashboard; route+record permission tests pass |
| 2 | Staff directory, CSV/XLSX import (dry-run/preview/commit), profile change requests, lifecycle | Self vs admin access and import failure tests pass |
| 3 | Memos: editor, recipient targeting, classification, approval states, attachments, inbox/acknowledgement, notifications | Cross-department access prevention + publishing workflow tests pass |
| 4 | Payslips: staging, manifest mapping, validation, quarantine, release approval, private publication, replacements | Malformed batch never publishes; cross-user download denied; retries idempotent |
| 5 | Leave + configurable approval engine + balance ledger + official letters (PDF) | Allowed workflows & concurrency tests pass; UNCONFIGURED policies block final actions |
| 6 | Service requests, reports, scoped exports | Status history, privacy and notification tests pass |
| 7 | Hardening: security review, permission-matrix audit, performance, restore drill, a11y, docs | AC-01…AC-15 mapped to executable evidence |

## Module map (modular monolith, Master Prompt §B)

```
app/
  Domain/
    Identity/          users, login identifiers, account lifecycle, auth audit, MFA
    Staff/             departments, staff_profiles, personnel import, change requests
    Correspondence/    memos, recipients, versions, acknowledgements
    PayrollDocuments/  payslip batches, validation, release, private files
    Leave/             types, entitlements, ledger, applications
    Approvals/         workflow definitions, stages, assignment, decisions
    OfficialLetters/   templates, references, issuance, PDF
    ServiceDesk/       requests, threads, units, SLA
    Notifications/     in-app + queued mail, retries
    Audit/             append-only audit log, actor/timestamp/record
  Http/Controllers/    Http/Requests/  Livewire/  Policies/  Jobs/  Notifications/  Support/
resources/views/  layouts/ public/ staff/ registry/ bursary/ approvals/ ict/ audit/
database/migrations/ factories/ seeders/
tests/Feature/ tests/Unit/   docs/
```

## Initial ERD (Phase 1 tables in **bold**; later phases listed for traceability)

- **`users`** — id, `staff_number` (unique, indexed), email (nullable/unique), `password_hash`, `status` (active/suspended/deactivated), `mfa_*`, `last_login_at`, timestamps. Login accepts staff number *or* email (AUTH-01).
- **`departments`** — code (unique), name, active.
- **`staff_profiles`** — user_id FK, legal/preferred names, department_id FK, designation, employment status, grade, contact fields split into *self-editable* vs *official* columns; official fields writable only via approval (PROF-03/04).
- **`roles` / `permissions` / `model_has_roles` / `model_has_permissions` / `role_has_permissions`** (Spatie).
- **`audit_logs`** — actor_id, action, subject_type/subject_id, context JSON (no secrets/salary payloads), created_at; append-only by policy.
- **`notifications`** (Laravel), **`jobs`**, **`failed_jobs`**, `sessions`, `password_reset_tokens`.
- Later: `staff_profile_change_requests`, `memos`, `memo_recipients`, `memo_attachments`, `documents`, `payslip_batches`, `payslips`, `leave_types`, `leave_entitlements`, `leave_balance_ledger`, `leave_applications`, `approval_workflow_definitions`, `approval_stage_definitions`, `approval_steps`, `letter_templates`, `issued_letters`, `service_requests`, `service_request_messages`, `system_settings`, `import_jobs`, `notification_delivery_attempts`.

Design rules enforced (PRD §6.1): unique staff number; stable ID references (no name keys); every private doc has explicit owner/recipients; approval history immutable; transaction boundaries for multi-record changes; private storage only; soft-delete vs deactivation distinction; audit log restricted and tamper-resistant.

## Testing conventions

- Pest 5, feature/integration tests on an isolated database (`staffportal_test`, Migrations+RefreshDatabase), synthetic factories only, never production data or SMTP.
- Test names carry the PRD ID they evidence (e.g. `test_ac01_valid_staff_login_shows_personal_dashboard (AUTH-01, AC-01)`).
- Every phase adds positive tests for the feature and negative tests for IDOR/cross-role access before the phase can be reported done.

## Phase 1 build order (current)

1. PostgreSQL portable service + databases (`staffportal`, `staffportal_test`).
2. Laravel skeleton in `C:\laragon\www\staffportal` (preserving PRD files), `.env` (pgsql, `Africa/Lagos`, `MAIL_MAILER=log`, database queue), `key:generate`, Git init.
3. Breeze (Livewire) + Spatie Permission; base migrations.
4. Phase 1 domain tables (users/staff_profiles/departments/audit) + factories + DEMO seeders (8 roles).
5. Design tokens/layouts → public landing page (PRD §3.1) → auth pages (login by staff ID or email, reset, errors, rate-limited).
6. Role-scoped dashboards (staff §3.2; admin §3.3 navigation) with policies.
7. Pest suite for AC-01/02/03/04/05 + role matrix; run, fix, report.
8. docs/ + README + commits.
