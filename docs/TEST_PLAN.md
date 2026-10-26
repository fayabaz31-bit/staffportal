# Test Plan — NfLV Staff Self-Service Portal

## 1. Objectives

Prove, with automated evidence, that P0 behaviours and the PRD acceptance criteria
(AC-01…AC-15) work, and that IDOR, cross-role and cross-unit access attempts are denied.
Tests are the primary verification artefact; do not substitute manual checks for them.

## 2. Levels

| Level | Location | Purpose |
|---|---|---|
| Feature / integration | `tests/Feature/**` | Every behaviour reachable through an HTTP request, using the real database |
| Unit | `tests/Unit/**` | Framework-free logic only |

Feature tests come first. The default is a feature test.

## 3. Environment

Configured in `phpunit.xml` (overrides `.env`):

| Setting | Value |
|---|---|
| `APP_ENV` | `testing` |
| `DB_CONNECTION` / `DB_DATABASE` | `mysql` / `staffportal_test` (isolated) |
| `CACHE_STORE` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `MAIL_MAILER` | `array` |
| `SESSION_DRIVER` | `array` |
| `BCRYPT_ROUNDS` | `4` (fast hashing) |
| `APP_TIMEZONE` | `Africa/Lagos` |

Tests use `RefreshDatabase` and only synthetic factory data. They never touch production data
or live SMTP.

## 4. Running

```powershell
# Whole suite
php artisan test --compact

# One file while iterating
php artisan test --compact tests/Feature/AccountLifecycleAndNotificationsTest.php

# One method
php artisan test --filter=test_repeated_failed_logins_are_rate_limited
```

Formatting: `php vendor/bin/pint --dirty` on changed PHP before completion.

## 5. Conventions

- File name `{Subject}Test.php`; methods prefixed `test_` and named for the user-visible result.
- Arrange/act/assert; one logical behaviour per test.
- Factories for all records; named factory states over raw attributes.
- `#[DataProvider]` (declared `public static`) for the same behaviour across enum/role/input
  values; the dataset key states the difference.
- Fakes created inside the test method: `Notification::fake()`, `Log::spy()`, `Mail::extend()`
  for failure injection. No mocking of the database.
- Test names reference the PRD ID where one applies (e.g. `test_ac05_...`).

## 6. Coverage map

| Area | Test class |
|---|---|
| Login, status gating, rate limiting, dashboard | `AuthenticationTest`, `AcceptanceCriteriaHardeningTest` |
| Password recovery | `PasswordResetTest` |
| Account lifecycle, roles, duties, notifications | `AccountLifecycleAndNotificationsTest`, `DutyAssignmentTest` |
| Append-only audit + viewer | `AuditTrailTest` |
| Profiles and change requests | `StaffProfileTest` |
| Personnel import / manual entry | `PersonnelImportTest`, `ManualPersonnelEntryTest` |
| Official departments and grouping | `DepartmentCatalogTest` |
| Registry work assignments | `RegistryAssignmentTest` |
| Registry correspondence and workflow | `RegistryCorrespondenceTest` |
| Organisation units, reporting lines, delegations | `OrganisationStructureTest` |
| Memos | `MemoCorrespondenceTest` |
| Payslip distribution | `PayslipDistributionTest`, `PlatformHardeningTest` |
| Leave workflow and letters | `LeaveWorkflowTest` |
| Service requests and reporting | `ServiceRequestAndReportingTest` |
| Platform hardening (disk, timezone, Zip-Slip, references, ICT gate) | `PlatformHardeningTest` |

AC-level mapping: `docs/ACCEPTANCE_EVIDENCE.md`.

## 7. Negative and security cases (mandatory)

- Unauthenticated access redirects to login; unauthorised roles get 403/404.
- Staff A cannot download Staff B's payslip, letter or notification.
- Cross-unit service-request isolation (Registry ↔ Bursary ↔ ICT).
- Internal administrative notes never shown to applicants.
- Malformed/Zip-Slip archives and non-PDF entries are rejected/quarantined.
- Suspended/deactivated accounts cannot authenticate.
- Password reset does not reveal whether an account exists.
- Notification deep links reject external destinations (open-redirect defence).
- Officers holding a different duty in the same unit are refused (e.g. personnel-only registry officer denied the registry service desk; non-approver denied payslip release).
- **Separation of duties (OD-19):** no role baseline combines payslip upload with release; the uploader is refused release of their own batch even when holding the release duty; granting the release duty to an uploader is refused; a lead may assign duties within their own department/unit but not outside it, not for their own account, and not account-wide administrative duties; a head of department reaches the **Team duties** page and sees only colleagues they may manage.
- **Registry chain (OD-20):** a Registry officer cannot decide at the Head of HR or Registrar stage; the originator cannot approve their own document; an officer outside the current stage cannot view or decide confidential correspondence; the Registrar's final approval is required before routine correspondence may be issued; Director-reserved correspondence cannot be issued before the Director decides; `ict_superadmin` is denied Registry correspondence and attachments.
- **Attachments and IDOR:** an attachment cannot be downloaded through a different document's URL; only the originator may attach while editable; downloads are audited; unsupported types and oversized files are rejected.
- **Organisation structure:** reporting lines reject self-reference, cycles and overlapping open line-management records; delegations grant scope only inside their date window and stop on revocation; non-managers cannot mutate the hierarchy.
- **Task vs approval vs issuance:** completing a work assignment does not approve a document; approval does not issue it; an issued document cannot be revised.
- **Canonical departments only:** a personnel import row whose `department_code` is not one of the seven official codes is marked invalid before commit; manual entry accepts only active canonical departments. Legacy departments are deactivated, not deleted, so historical records keep their foreign key and are visibly marked "unmapped".

## 8. Current result

**198 tests / 1196 assertions / 0 failures** (`php artisan test --compact`), ≈68 s on the local
Windows/MySQL machine. One test is reported as *risky* by PHPUnit (it exercises a path without
its own assertion); the suite still exits successfully.

## 9. Known gaps

- **No load/soak test.** AC-14 evidence is limited to throttling, row locking and transaction
  tests. A documented concurrency model and p95 measurement are still required (Master Prompt §H).
- **Restore drill not executed** (AC-12); only a runbook exists.
- **No real-browser test** — `laravel/dusk` is not installed (do not add without approval).
- **No XLSX import test** because XLSX import is not implemented.
- Email delivery is exercised with the `array` mailer and an injected failing transport; no live
  SMTP is ever used.
