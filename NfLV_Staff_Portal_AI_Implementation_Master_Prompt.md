# MASTER IMPLEMENTATION PROMPT — NfLV STAFF SELF-SERVICE PORTAL

**Project:** Nigeria French Language Village (NfLV), Badagry — University Staff Self-Service Portal  
**Source of truth:** Attached `NfLV_Staff_Portal_PRD.pdf` (version 1.0, 13 October 2026, 27 pages).  
**Initial users:** Approximately 500 staff plus authorised administrative users.  
**Development environment:** Windows, **Laragon already installed and configured**.  
**Task:** Plan, implement, test and document a working, secure, maintainable application—not just UI mockups or scaffolding.

> **Agent instructions:** Read the complete attached PRD before writing code. Its numbered requirements (AUTH, PROF, MEM, PAY, LEAVE, DOC, SRV, NFR and AC) are authoritative. When these instructions provide technical implementation detail not specified in the PRD, treat it as a proposed engineering default, not institutional policy. Do not change or weaken a P0 requirement to make implementation easier. If an institutional rule, real credential, official document, logo, signing authority, SMTP detail or payroll format is missing, create an explicit, safe, configurable placeholder and record the outstanding decision in `docs/OPEN_DECISIONS.md`. Do not fabricate a real institutional decision.

---

## A. HOW TO WORK

You are the senior full-stack engineer, security-minded architect, database designer, UX designer and QA engineer responsible for implementing this project. Work autonomously where specifications are complete, but **stop and request decisions only when necessary to prevent incorrect institutional behaviour** (e.g., signing authority, entitlement rules, official payroll mappings). Otherwise proceed with safe technical defaults, document assumptions and report each milestone.

1. **First inspect the workspace.** Check whether an existing repository/application is present, inspect Git status, read `README`, `.env.example`, package manifests and framework configuration. Never overwrite existing work, delete files, reset a database or run destructive commands without a documented reason and explicit approval.
2. **Inspect the local Laragon environment**, using available command-line tools: `php -v`, `composer -V`, `node -v`, `npm -v`, `git --version`, `php -m`, Laravel version if present, database client/service and the selected local PHP configuration. Report missing prerequisites and concise manual actions. **Do not reinstall Laragon**. Confirm PostgreSQL availability; Laragon commonly includes MySQL/MariaDB rather than PostgreSQL. If PostgreSQL is not installed or running, ask for installation/connection details or provide the exact optional setup steps. Do not silently substitute MySQL because the approved PRD specifies PostgreSQL.
3. Use stable, supported, mutually compatible versions of Laravel, PHP, Livewire, Tailwind, PostgreSQL and dependencies. Check what the environment supports before selecting exact versions. Use compatible authentication and MFA facilities. Do not invent a package name, version or Artisan command.
4. Create a Git branch or initialise Git only with permission where appropriate. Keep `.env`, credentials, dumps, real staff data and private documents out of Git. Make incremental, meaningful commits when permitted.
5. Create a planning and traceability directory with `docs/IMPLEMENTATION_PLAN.md`, `docs/ARCHITECTURE.md`, `docs/REQUIREMENTS_TRACEABILITY.md`, `docs/OPEN_DECISIONS.md`, `docs/SECURITY_MODEL.md`, `docs/TEST_PLAN.md` and `docs/SETUP_WINDOWS_LARAGON.md`.
6. Implement **vertical slices**: database, validation, policies, business services, routes, UI, tests and documentation together. A beautiful screen is not a completed feature if its actions are not wired to real database operations and authorisation.
7. At the end of each phase, run relevant checks and report: files changed, migrations applied, tests passed/failed, manual checks, blockers, next phase. If execution context is reset, read project documentation and Git state before continuing.
8. Never claim tests passed unless actually executed. If tooling is unavailable, identify unverified work clearly. Do not claim the system is production-ready until all P0 acceptance criteria are met and security/recovery tests have passed.

## B. ARCHITECTURE AND TECHNOLOGY

Implement a **Laravel modular monolith**. No microservices or separate SPA are necessary for the initial 500-staff release.

- Server: Laravel + PHP; Laravel service classes/actions, jobs, events/listeners, requests, policies, notifications and database transactions.
- Frontend: Blade + Livewire + Tailwind CSS + Vite; use minimal Alpine.js for appropriate client-side interactions. Responsive first; avoid an unrelated React frontend.
- Database: PostgreSQL, normalised relational design, appropriate foreign keys, unique constraints, indexes and transaction boundaries.
- Auth: framework-supported login, secure reset, session rotation, rate limiting, MFA for privileged accounts and deactivation checks.
- Permissions: Laravel policies/gates and a tested role/permission implementation (Spatie Laravel Permission only if compatible and justified); add **record-level, department-level and workflow-level checks**. No blanket bypass for technical superadmins.
- Queues: Laravel database-backed queue initially; optional Redis behind configuration when needed. Use queued mail and bulk notification jobs; implement retry behaviour and failed-job visibility.
- PDFs: compatible server-side PDF generation library; preserve final immutable issued PDF and the template version/approval record. Do not print decorative signature images as if they were authentic authorisation.
- Files: private Laravel storage disk, randomised server filenames, MIME/file signature checks, size limits, controlled download response, audit events. Never expose payslips via `public/storage` or predictable public URLs.
- Email: Laravel Mail through institutional SMTP when configured; `log` or local email catcher in development. Do not send live messages to real staff from seed/demo data.
- Testing: Pest or PHPUnit, feature/integration tests, HTTP authorisation tests, database tests and critical workflow tests. Use synthetic data only.
- Operations: production guidance for supported Linux + Nginx + PHP-FPM, HTTPS, worker supervision, cron scheduler, database/storage backups and monitoring. **Do not run production Linux setup instructions against Windows Laragon.**

Suggested separation (adapt to selected Laravel version):

```text
app/
  Domain/
    Identity/
    Staff/
    Correspondence/
    PayrollDocuments/
    Leave/
    Approvals/
    OfficialLetters/
    ServiceDesk/
    Notifications/
    Audit/
  Http/Controllers/
  Http/Requests/
  Livewire/
  Policies/
  Jobs/
  Notifications/
  Support/
resources/views/
  layouts/
  public/
  staff/
  registry/
  bursary/
  approvals/
  ict/
  audit/
database/migrations/
database/factories/
database/seeders/
tests/Feature/
tests/Unit/
docs/
```

Do not create unnecessary abstractions or duplicated permission logic; align Laravel conventions and domain boundaries.

## C. CONFIRM PRODUCT REQUIREMENTS AND DESIGN SYSTEM

Implement the **public landing page and login entry point** as explicit first-class features (PRD §3.1): institution-approved name/logo/colour scheme, service overview, login access, password recovery, ICT help link, privacy/terms links and mobile-responsive layout. If official assets are not available, use clean text identity and a clearly marked replaceable asset; **do not invent or falsely present an official crest**.

Design professionally with restrained spacing, accessible typography and institutional styling, avoiding generic crowded dashboard templates. Build reusable components: page shell, accessible sidebar, top bar, breadcrumbs, search/filter controls, cards, tables, pagination, status labels, confirmation dialogs, loading/error/empty states, notices and forms. Respect WCAG 2.2 AA for core journeys where feasible. Include screen designs for:

1. Public landing + sign-in + reset/MFA states.
2. Staff dashboard; profile, profile-change request and document centre.
3. Personal memo inbox/details/acknowledgement.
4. Payslip history and authorised download.
5. Leave application form, status timeline, balance and approval letter.
6. Registry/Bursary service request centre and thread.
7. Registry dashboard (directory, memos, personnel changes, leave verification, letters).
8. Bursary dashboard (payslip batch imports, validations, release approval, salary enquiries).
9. Supervisor/Director queue (review, recommend, return, approve/reject as authorised).
10. ICT admin dashboard (accounts, roles, system settings, logs **without unrestricted sensitive-content viewing**).
11. Institution oversight and auditor read-only dashboards within explicit scope.

Keep unauthenticated pages free of private material. Use route-based layouts and conditional navigation, **plus server-side enforcement for every action**.

## D. DATA MODEL AND MIGRATIONS

Create a detailed schema/ERD **before coding all migrations**. At minimum address the PRD entities:

- `users`, `staff_profiles`, `departments`, roles/permissions/role assignments
- `memos`, `memo_recipients`, `documents`
- `payslip_batches`, `payslips`
- `leave_types`, `leave_entitlements`, `leave_applications`, `approval_steps`
- `letter_templates`, `issued_letters`
- `service_requests`, `notifications`, `audit_logs`, `system_settings`

Add only justified supporting entities, e.g. `staff_profile_change_requests`, `memo_versions`, `memo_attachments`, `service_request_messages`, `approval_workflow_definitions`, `approval_stage_definitions`, `leave_balance_ledger`, `payslip_batch_items`, `document_access_grants`, `document_download_events`, `import_jobs`, `notification_delivery_attempts`, `public_holidays`, `staff_employment_categories`, `reference_sequences`.

Requirements:

- Unique, indexed institutional staff number; account and staff profile linked by a stable ID. Handle staff lifecycle and null/optional institutional email safely.
- Use normalised relationships, FK constraints, appropriate unique indexes (including payroll period + staff + active publication version where feasible), soft deletes only where valid, and audit history for material changes.
- Mark private document ownership, classification, recipient grants, storage path, original metadata, hash/checksum, MIME type, size, version, uploader and publication state.
- Distinguish staff profile correction requests from approved canonical fields. Do not let self-service fields bypass sensitive-change approval.
- Workflow state transitions must be explicit and guarded; timestamp and attribute each action to its human or system actor.
- Prevent concurrent double approvals and balance modifications with transactions, row locks/optimistic version checks and idempotency where appropriate.
- Keep monetary values in safe decimal/integer representations; do not calculate payroll in this product.
- Add indexes for staff number, foreign keys, dates, unread correspondence, assigned approvals and request statuses. Avoid indexing sensitive plain-text content unnecessarily.
- Audit log entries should be append-only under application policy with restricted retention/visibility. Do not record passwords, auth tokens, full salary records or secret values.
- Seed only fictional staff, departments, role assignments, memos, leave records and sample payslips. Seed data should visibly be **DEMO DATA** and be disabled in production.

## E. ACCESS CONTROL: DEFINE, IMPLEMENT AND TEST

Use the following roles from PRD §2.1, and implement permissions as actions/scopes, not simply a role-name comparison:

- `staff`
- `hr_registry_admin`
- `bursary_admin`
- `head_of_department` / `supervisor`
- `director` / `authorised_approver`
- `ict_superadmin`
- `institutional_superadmin`
- `auditor_readonly`

Create `docs/ROLE_PERMISSION_MATRIX.md` describing read/create/update/review/approve/publish/export/deactivate rights by resource and scope (`self`, `assigned`, `department`, `institution`), including confidentiality controls.

In particular:

- Staff can only access own sensitive records and targeted correspondence.
- Department heads can see only the cases and operational reports granted to them, not unrestricted payroll.
- Registry can verify personnel/leave data without access to every payslip.
- Bursary can manage authorised payslip batches, but cannot silently change HR decisions.
- ICT can support accounts, settings and operations but **cannot automatically view private documents or authorise HR/Bursary transactions**.
- Institutional oversight is permission-scoped and audited, not an unrestricted privacy bypass.
- Auditor rights are read-only and explicitly scoped.
- Approval is valid only when the user is an active, authorised assignee in the current workflow stage.
- Admin impersonation, if later requested, is **not** part of MVP; never silently implement it.

Write negative tests for IDOR, file downloads, route access, admin UI actions, exported reports, guessed identifiers, direct HTTP requests, Livewire calls and disabled accounts.

## F. FEATURE IMPLEMENTATION — ALL P0 REQUIREMENTS

### F1. Identity, onboarding and profiles — AUTH-01…09; PROF-01…06,08

Build sign-in via staff ID or approved email, secure recovery, session invalidation, account status checks, enforced MFA for privileged accounts and relevant auth audit events. Build an authorised CSV/XLSX personnel import workflow with dry-run preview, mapping validation, duplicate detection, rollback/rejection and import result report. Do not overwrite validated official records without review. Profile shows official fields read-only except explicitly self-editable fields; material changes use a request + verification + approval + history. Support account activate/suspend/deactivate without deleting historical records.

### F2. Memos and circulars — MEM-01…08

Provide draft editor, memo category/classification, supporting files, explicit recipient selection (individual, department, group, all), preview, conditional review/approval, immutable published version, recipient snapshot, publish action, private inbox, download, read/acknowledgement events and email + in-app notifications. Avoid granting access solely because someone joins a department **after** publication unless a configured distribution rule authorises it. Prevent unauthorised content exposure through search results, notification payloads or attachments. Implement withdrawal with audit trail.

### F3. Payslip distribution — PAY-01…09

**This portal does not run payroll.** Bursary imports approved payslip documents and matching metadata. Build:

1. Secure single-file and batch import (support a documented file naming convention or validated manifest CSV + PDFs; keep adapter flexible because actual Bursary format is not yet confirmed).
2. Dry-run validation for staff identifier, matching payroll month/year, file integrity, duplicate, missing and unknown staff, size/type, safe paths.
3. Quarantine and detailed errors (the PRD says unresolved material errors block release).
4. Explicit approval by authorised release officer (separation of duties where configured).
5. Idempotent atomic publication, individual private ownership, audit trail, historic download.
6. Controlled withdrawal/replacement retaining prior version and reason, with notifications.
7. Synthetic demo batch for testing. No external email attachment containing payslip details.

Never place real payslips in the webroot or let staff enumerate file URLs.

### F4. Leave and configurable approval engine — LEAVE-01…11

Build leave types, entitlement configuration, per-staff/category balance or ledger, application drafts/submission, date validation, overlap checking, attachments, history, supervisor review if configured, HR verification if configured, final authorised approval or rejection, correction requests, withdrawal/cancellation workflows, balance update and approved PDF letter issuance. Model workflow definitions and stage configuration in the database—not one hardcoded supervisor → HR → Director chain.

**Institutional rules NOT provided in the PRD:** leave allowances, eligibility, holidays, weekend counting, cutoffs, carry-forward, fractional days, delegated approvers, final signing authority, letter reference format. Create safe draft settings marked *unconfigured*; block real final approval/balance deductions/official issuance until the necessary policies are configured and approved. Use a clearly labelled **fictional demo policy** only for synthetic tests. All dates/timezones should support the institution's local timezone (`Africa/Lagos` for display/business calendar).

Enforce allowed state transitions; protect against duplicate submits, overlapping approved leave, simultaneous approvals, retries, and negative balances unless an approved policy explicitly allows exceptions.

### F5. Official letters — DOC-01…07

Build versioned template management, approved merge fields, reference sequence, PDF rendering, controlled issuance workflow, private delivery and retrieval. Use a sandboxed/allowlisted template substitution approach—do not evaluate user-supplied PHP/Blade expressions as executable code. Persist template version, issuance actor, timestamp, approver and final document hash. For an approval letter, require recorded approval before assigning final/issued status. No fake approved signature. Optionally allow draft preview with a conspicuous DRAFT watermark. Annual increment/promotion templates are P1; scaffold placeholders only if it does not interfere with P0.

### F6. Registry/Bursary requests — SRV-01…06

Staff submits categorised requests, description and validated attachments; system assigns immutable tracking ID, routes to appropriate unit, supports assignment, internal notes separated from staff-visible replies, response thread, escalation, closure, reopen policy if configured, status history and notifications. Salary enquiries are confidential; one unit's administrators must not see another unit's confidential case without grant.

### F7. Notifications, audit and reporting — PRD §§4.8–4.9

Provide in-app inbox + queued generic email notifications for memos, payslips, leave changes, letters and service requests. Maintain retries/failures and idempotent delivery jobs. Send no live emails until SMTP configured. Dashboard metrics and permitted reports should be derived from authoritative records. Audit changes to roles, approvals, sensitive profiles, memo publication, payslip batches, letter issuance and downloads as appropriate. Do not leak salary information in management dashboards or raw exports.

## G. DELIVERABLES AND IMPLEMENTATION PHASES

Do not try to code the entire portal in a single unreviewed change. Execute in order:

**Phase 0 — Discovery/environment:** Inspect repo/Laragon/version compatibility. Parse the entire PRD. Produce requirements traceability and decisions list. Set up Git/testing conventions. **Exit:** confirmed environment, technical choices, explicit blockers.

**Phase 1 — Foundation and UI:** Create Laravel app if needed, connect PostgreSQL, establish design tokens/layouts, build public landing/login, secure auth/MFA, roles/policies, staff dashboard shell, migrations/factories, seed synthetic demo accounts and baseline tests. **Exit:** every role can sign in to a working scoped dashboard; route and record permissions tested.

**Phase 2 — Staff directory/profiles:** Build import preview/validation and safe commit, personnel view, profile change requests/verification, lifecycle. **Exit:** tested self vs admin access and import failures.

**Phase 3 — Memos/correspondence:** Build editor, recipient targeting, classification, approval states, private attachments, inbox/acknowledgement, notifications. **Exit:** cross-department access prevention and publishing workflow tests pass.

**Phase 4 — Payslips:** Build batch staging, manifest mapping, validation, approval, private publication, replacements and notifications. **Exit:** malformed or mismatched batch never publishes; cross-user download denied; retries do not duplicate publication.

**Phase 5 — Leave/workflows/letters:** Build policy configuration, entitlements, approval engine, state transitions, balance ledger, templates and final PDF issuance. **Exit:** allowed workflows and concurrency tests pass; missing institutional rules keep sensitive production actions blocked.

**Phase 6 — Service requests/reports:** Build service desk, assigned unit inbox, reporting and scoped exports. **Exit:** status history, privacy controls and notifications tested.

**Phase 7 — Hardening and documentation:** Security review, permission matrix audit, performance checks, restore drill instructions/test on safe local backup, accessible UI review, error/empty states, application docs, runbooks and environment samples. **Exit:** map PRD acceptance tests AC-01…AC-15 to executable evidence, and identify remaining production approval requirements.

Do not mark a phase completed with only routes, placeholders, a screenshot or stub responses. Implement connected CRUD/actions, permissions, error handling, real database persistence and tests.

## H. MANDATORY TEST COVERAGE

Create real automated tests corresponding to PRD §11 **AC-01 to AC-15**, referencing IDs in test names or docstrings. Include:

- Staff login → personal dashboard and suspended-account denial.
- Unauthorised URL and staff document denial (both controller and Livewire endpoints).
- Staff A cannot download or preview Staff B's payslip, letter, profile, leave or confidential memo.
- Memo targeting by department and individually, with exact authorised recipients.
- Bad batch file/unknown staff/mismatched mapping/duplicate payroll record rejection.
- Leave submission and correct dynamic route; rejection comments preserved; approval produces correctly scoped official letter and entitlement record changes.
- Profile field correction review before canonical write.
- Permission change tracked and effective immediately.
- Mail delivery failure leaves in-app record intact and retryable.
- Competing approval requests cannot result in duplicate decisions/deductions or inconsistent states.
- Deactivation blocks authentication while preserving historical data.
- Files cannot be fetched from predictable public URLs; malformed uploads are rejected.
- Restore drill for database and private files must be documented and actually tested in a safe environment before release.
- Load test using a documented, agreed concurrency model (do not equate 500 accounts with 500 simultaneous sessions). Measure p95 response and validate the PRD's proposed two-second target.

Use migrations + factories + isolated test database; tests must never point at production data or SMTP.

## I. LOCAL WINDOWS/LARAGON DEVELOPER EXPERIENCE

Write reproducible `docs/SETUP_WINDOWS_LARAGON.md` based on **actual detected local environment**. Cover:

- Existing Laragon project root/path; don't assume `C:\\laragon\\www` without checking.
- CLI PHP selection, Composer, Git, Node/npm and necessary PHP extensions (`pdo_pgsql`/`pgsql`, fileinfo, mbstring, openssl, intl etc. as required by chosen versions).
- PostgreSQL service/credentials if present; otherwise exact missing-prerequisite notice and user approval before installation.
- Creating application/database/test database only with safe commands and explicitly named paths.
- `.env.example` values for `APP_URL`, PGSQL connection, mail log/test configuration, queues, local storage and app timezone; do not include real passwords.
- `composer install`, `npm install`, `php artisan key:generate`, `php artisan migrate`, safe demo seeding, `npm run dev`, Laravel queue worker command and local test commands as appropriate.
- Local HTTPS/domain choices if available; clarify dev/prod differences.
- Troubleshooting Windows path, PHP extension, port collision, Vite/HMR, file permissions, PostgreSQL connection and mail issues.
- Exact command to reset synthetic demo data only, with destructive operation warning; no silent `migrate:fresh` against valuable data.

Provide a `.env.example`, non-sensitive example configuration and a README with the confirmed local URL and instructions only once known.

## J. SAFETY AND QUALITY GATES

- Validate every server-side input; authorise before querying/streaming private records; use scoped queries and signed/short-lived links only where they supplement, not replace, authorisation.
- All financial and HR documents stay in private storage, served through authorised controllers with appropriate cache headers.
- Validate upload MIME/content and filename/path handling. Reject executables and unsupported file types. Consider scanning, quarantine and content security controls.
- Encrypt secrets and protect environment files; mask sensitive values in logs and exception screens.
- Record timestamps, actors, version history, transaction outcome and reason for privileged changes.
- Preserve privacy between Registry, Bursary, ICT and management functions.
- Do not build payroll calculation, salary payment, recruitment, student management or native mobile application (PRD §15).
- Do not implement unapproved digital-signature semantics or institutional legal policies.
- Deliver documentation for Nigerian data protection review, retention policy placeholders and access review procedures, but **do not claim formal legal compliance without institutional assessment**.
- Keep code idiomatic and maintainable; avoid introducing Docker/Kubernetes, an SPA, microservices, expensive SaaS or unnecessary paid services when local Laravel/Laragon suffices.

## K. OUTPUT CONTRACT FOR EVERY WORK SESSION

Return concise, verifiable progress, not declarations of success:

```text
Current phase:
PRD IDs addressed:
Environment/framework versions confirmed:
Files created/changed:
Database migrations run:
Commands executed:
Tests run and their actual results:
UI pages and working actions completed:
Security checks performed:
Open institutional decisions/blockers:
Next implementation action:
```

Maintain `docs/REQUIREMENTS_TRACEABILITY.md` with each P0 ID, implementing components and test cases; maintain `docs/OPEN_DECISIONS.md` with owner, impact, proposed options and status. Produce screenshots only if the execution environment actually supports rendering them.

## L. START NOW — FIRST EXECUTION

1. Read the complete uploaded `NfLV_Staff_Portal_PRD.pdf` and these instructions.
2. Inspect existing project files and current Laragon dependencies without changing or deleting anything.
3. Provide an environment compatibility report: installed PHP, Composer, Node/npm, Git, PostgreSQL availability, PHP extensions and active working directory. Detect whether the app already exists.
4. Create the project implementation checklist, module map, initial database ERD and role/permission matrix; mark all undecided institutional business rules.
5. If prerequisites are satisfied, create the Laravel application (or safely continue the existing one), local config, auth/permissions skeleton, migrations and **first real deliverable: polished landing page + working login + role-appropriate dashboard shells**.
6. Add automated tests for this first vertical slice and run them.
7. Report evidence and proceed to the next phase in sequence. Do not stop merely after producing a plan, except when an essential prerequisite requires action by the user.

**Success condition:** A working, tested, privacy-aware portal meeting all P0 requirements and acceptance criteria of the attached PRD, with unresolved institution-specific decisions clearly documented and a safe path to pilot deployment for 20–30 test users before onboarding ~500 staff.
