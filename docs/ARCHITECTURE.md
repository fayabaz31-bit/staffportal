# Architecture — NfLV Staff Self-Service Portal

## 1. Purpose and scope

A Laravel modular monolith that gives NfLV staff secure self-service access to memos,
payslips, leave, official letters and service requests, while giving Registry, Bursary,
ICT, approvers and auditors scoped administrative tools. The portal **does not run payroll**;
it distributes documents produced elsewhere and records HR/leave/registry workflows.

## 2. Confirmed stack

| Layer | Choice (as installed) |
|---|---|
| Language | PHP 8.3 (local 8.3.33) |
| Framework | Laravel 13 (`laravel/framework ^13.17`) |
| Views | Blade only (no Livewire/Inertia/SPA) |
| CSS/JS | Tailwind CSS 4 + Vite 8 via `laravel-vite-plugin` |
| Database | MySQL 8.4.3 (Laragon local); dedicated `staffportal_test` for tests |
| Auth | Hand-rolled session auth (`AuthenticatedSessionController`) — no Breeze/Jetstream |
| Authorisation | Native PHP 8.3 enum `App\UserRole` (broad unit/dashboard) + **duties** (`App\DutyCatalog`, `duties`/`duty_user`) enforced by Laravel Gates in `AppServiceProvider` — no Spatie package |
| PDF | `barryvdh/laravel-dompdf` |
| Queue | `database` in local/prod config, `sync` in tests |
| Mail | `log` in development, `array` in tests |
| Tests | PHPUnit 12 |

## 3. Request lifecycle

1. **Route** (`routes/web.php`) applies `guest`, `auth` or `can:<gate>` middleware, so
   unauthorised roles are rejected before a controller runs.
2. **Controller** (`app/Http/Controllers/**`) authorises again with `Gate::authorize(...)` for
   anything state-changing, validates the request, and delegates to models/notifiers inside a
   transaction where several records change together.
3. **Model** (`app/Models/**`) enforces invariants (e.g. `AuditLog` is append-only;
   `ReferenceSequence::next()` is atomic; concurrency uses `lockForUpdate()` and a
   `concurrency_version` column).
4. **View** (`resources/views/**`) is rendered server-side with escaped data.
5. **Side effects** — `PortalNotifier` writes the in-app notification synchronously and queues
   a generic email; `AuditLog::record()` appends an audit row.

## 4. Directory layout

```
app/
  Http/Controllers/        feature controllers + Auth/ for sign-in and password reset
  Http/Middleware/         (framework defaults; gates handle authorisation)
  Models/                  domain models
  Notifications/           PortalInboxNotification, PortalEmailNotification, PortalNotifier
  Observers/UserObserver.php         resets duties to the role baseline on create/role change
  Providers/AppServiceProvider.php   duty-based gates + "login" rate limiter
  Support/                 WorkflowEngine, AssignmentWorkflow, CorrespondenceService,
                           RegistryWorkflowCatalog, DashboardMetrics
  DepartmentCatalog.php    canonical seven official departments + idempotent sync
  UserRole.php             role enum, labels, demo identities, dashboard tiles
  DutyCatalog.php          duty ("desk") catalogue and role→baseline duty mappings
resources/views/           one folder per domain + layouts/ and partials/
database/migrations/       schema history
database/factories/        synthetic test data
database/seeders/          DEMO seed data (disabled outside local/testing)
tests/Feature/ tests/Unit/ PHPUnit suites
docs/                      this document and its siblings
```

## 5. Domain processes

| Domain | Core tables | Key behaviour |
|---|---|---|
| Identity | `users`, `duties`, `duty_user` | staff number/email login, `status` gate on every gate, account activate/suspend/deactivate, duty ("desk") assignment with role baselines, append-only auth audit, temp-password issuance |
| Staff | `departments`, `staff_profiles`, `profile_change_requests`, `personnel_import_batches` | self-editable contact fields; official fields require review/approval; CSV dry-run preview then commit |
| Correspondence | `memos`, `memo_recipients` | draft → snapshot recipients → publish → acknowledge/withdraw; recipient snapshot is immutable |
| Registry management | `departments`, `organisational_units`, `reporting_relationships`, `delegations` | Seven canonical departments; units, reporting lines and time-bounded delegations configured as data; reporting cycles/overlaps rejected; all changes audited |
| Assigned work | `work_assignments`, `assignment_comments`, `assignment_events`, `assignment_attachments` | Draft → assigned → acknowledged → in progress → submitted → returned/resubmitted → completed; supervisor completes, assignee cannot self-close; private evidence |
| Registry correspondence | `correspondence_categories`, `correspondence_templates`, `correspondence_documents`, `document_versions`, `document_attachments` | Versioned drafts, unique references, configurable approval workflow, approval separate from issuance, private PDF/attachments |
| Workflow engine | `workflow_definitions`, `workflow_versions`, `workflow_steps`, `workflow_instances`, `workflow_actions` | Versioned, effective-dated definitions; instances pin their version; stage authority comes from the step's required duty; self-approval refused |
| Payroll documents | `payslip_batches`, `payslips` | ZIP/PDF staging with Zip-Slip-safe extraction, validation/quarantine, approval, private publication, withdrawal |
| Leave | `leave_types`, `leave_entitlements`, `leave_applications`, `leave_approval_actions` | business-day calculation, overlap checks, multi-stage approval, optimistic concurrency, official letter generation |
| Letters | `official_letters` | record-before-issue, private streaming, SHA-256 hash, reference sequence |
| Service desk | `service_requests`, `service_request_messages`, `service_request_status_logs` | immutable tracking reference, unit routing, internal notes hidden from applicants, status history |
| Notifications | `notifications`, `jobs`, `failed_jobs` | sync in-app row + queued generic email; failures retryable |
| Audit | `audit_logs` | append-only; actor, action, subject, JSON context, IP, timestamp; no secrets/salary payloads |
| Reporting | `report_export_audits` | scoped exports with privacy filters and audit rows |

## 6. Reference numbers

Human-readable references (e.g. `REQ-2026-0001`) are generated atomically by
`App\Models\ReferenceSequence::next($scope, $year, $floor = 0)`, which locks the
`reference_sequences` row inside the surrounding transaction. This removes the
read-count-then-insert race that a "max + 1" approach suffers under concurrency.

## 6.1 Configurable workflow engine

`WorkflowDefinition` groups `WorkflowVersion` records; each version owns ordered `WorkflowStep`
records that name the duty required to act and the action taken on approval. Starting a document
creates a `WorkflowInstance` pinned to the currently effective version. `WorkflowEngine` resolves
the current step, re-checks the actor's duties, refuses self-approval, records a `WorkflowAction`,
and advances or closes the instance. Correspondence status is derived from the engine, not from
hard-coded controller chains. Seeded definitions (`RegistryWorkflowCatalog`) are pilot defaults;
changing a definition publishes a new version and never rewrites work already in progress.

`CorrespondenceService` adds a further, deliberate separation: an approved document is not an
issued document. Only approval permits issuance, and issuance freezes the document.

## 6.2 Task completion versus approval

`work_assignments` are operational tasks. Completing one is a supervision act, not a document
approval. `correspondence_documents` carry their own approval and issuance fields, and the two
lifecycles never share a status column. This prevents the common failure mode where "done"
silently means "approved".

## 7. Storage

Private documents use the `private` filesystem disk (`storage_path('app/private')`), never the
public webroot. Downloads are streamed by controllers after an ownership/role check, with
`no-store` cache headers. Archive extraction rejects absolute paths, `..` traversal, symlinks
and oversized entries.

## 8. Timezone

The business timezone is `Africa/Lagos`, wired through `config/app.php` via `APP_TIMEZONE`
(default `UTC`) and set in `.env`, `.env.example`, `.env.testing` and `phpunit.xml`.

## 9. Known constraints

- Queued email needs a running queue worker; without one the in-app row still appears.
- MFA (OD-15) and SSO (OD-17) are not implemented.
- Seeded Registry workflows are **pilot defaults**, not a statement of final institutional
  reserved-matter policy; they are editable and versioned (OD-20).
- Correspondence supports one configured approval chain per category; parallel approvers,
  conditional branches beyond category selection, and DB-managed step editing UI are not built
  (the data model supports versions and steps, but there is no admin screen yet).
- Several institutional rules remain open — see `docs/OPEN_DECISIONS.md`; affected actions are
  blocked or marked UNCONFIGURED rather than guessed.
