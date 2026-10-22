# Requirements Traceability — NfLV Staff Self-Service Portal

Maps the master prompt's P0 requirement groups to implementing components and automated
evidence.

> **Honesty note.** Mapping is at **requirement-group / ID-range** level (e.g. "AUTH-01…09").
> The PRD's individual requirement text was not re-consulted line-by-line for each numeric ID
> when this file was written, so individual IDs within a group are not asserted separately.
> Where a group is only partially met, that is called out. Acceptance criteria AC-01…AC-15 are
> traced precisely in `docs/ACCEPTANCE_EVIDENCE.md`.

## Coverage summary

| Group | Requirement range | Components | Evidence | Status |
|---|---|---|---|---|
| Identity & auth | AUTH-01…09 | `Auth/AuthenticatedSessionController`, `Auth/PasswordResetLinkController`, `Auth/NewPasswordController`, `UserRole`, `DutyCatalog`, `Http/Controllers/IctAccountController`, `AppServiceProvider` duty gates + login limiter | `AuthenticationTest`, `PasswordResetTest`, `AccountLifecycleAndNotificationsTest`, `DutyAssignmentTest`, `PlatformHardeningTest` | **Partial** — login, status gating, reset, rate limiting, account lifecycle and duty/role assignment done; **MFA (AUTH-10) not implemented (OD-15)**; SSO (AUTH-11) deferred |
| Profiles & onboarding | PROF-01…06, 08 | `StaffProfileController`, `ProfileChangeRequestController`, `RegistryProfileChangeRequestController`, `PersonnelImportController`, `PersonnelDirectoryController`, `StaffProfile`, `ProfileChangeRequest`, `PersonnelImportBatch` | `StaffProfileTest`, `ManualPersonnelEntryTest`, `PersonnelImportTest` | **Partial** — CSV import + review workflow done; **XLSX import not implemented** |
| Memos | MEM-01…08 | `MemoController`, `Memo`, `MemoRecipient` | `MemoCorrespondenceTest` | **Partial** — authoring, targeting, snapshot, publish/withdraw, acknowledgement done; **attachments and richer approval states not implemented**; restricted classifications blocked until OD-11 |
| Payslips | PAY-01…09 | `BursaryPayslipBatchController`, `StaffPayslipController`, `PayslipBatch`, `Payslip` | `PayslipDistributionTest`, `DutyAssignmentTest`, `PlatformHardeningTest` | **Partial** — ZIP/PDF staging, validation/quarantine, approval with mandatory separation of duties (OD-19: release officer ≠ uploader), private streaming, withdrawal done; **manifest-CSV adapter not implemented (OD-07)** |
| Leave & approvals | LEAVE-01…11 | `StaffLeaveController`, `LeaveApprovalController`, `LeaveType`, `LeaveEntitlement`, `LeaveApplication`, `LeaveApprovalAction` | `LeaveWorkflowTest` | **Partial** — submission, business days, overlap, multi-stage engine, concurrency, letters done; **balance ledger, public holidays, carry-forward, DB-configured stage definitions outstanding (OD-04, OD-10)** |
| Official letters | DOC-01…07 | `OfficialLetterController`, `LeaveApprovalController`, `OfficialLetter`, `ReferenceSequence` | `LeaveWorkflowTest`, `AuditTrailTest` | **Partial** — record-before-issue, private streaming, SHA-256 hash, reference sequence done; **template management not implemented; final signing authority open (OD-05)** |
| Service requests | SRV-01…06 | `StaffServiceRequestController`, `UnitServiceRequestController`, `ServiceRequest`, `ServiceRequestMessage`, `ServiceRequestStatusLog` | `ServiceRequestAndReportingTest`, `PlatformHardeningTest` | **PASS** for implemented scope; categories/SLA remain open (OD-16) |
| Notifications (F7/§4.8) | F7 | `NotificationController`, `PortalNotifier`, `PortalInboxNotification`, `PortalEmailNotification` | `AccountLifecycleAndNotificationsTest` | **PASS** |
| Audit (F7/§4.9) | F7 | `AuditLogController`, `AuditLog` | `AuditTrailTest`, `AccountLifecycleAndNotificationsTest` | **PASS** |
| Reporting | F7 / §4.9 | `ReportController`, `ReportExportAudit` | `ServiceRequestAndReportingTest` | **PASS** for implemented scope |
| Registry department management | Department grouping, personnel detail upload, duty assignments, configurable correspondence workflow, organisation hierarchy, Registry dashboards | `DepartmentCatalog`, `PersonnelDirectoryController`, `PersonnelImportController`, `WorkAssignmentController`, `RegistryCorrespondenceController`, `OrganisationController`, `DashboardMetrics`, `WorkflowEngine`, `CorrespondenceService` | `DepartmentCatalogTest`, `PersonnelImportTest`, `RegistryAssignmentTest`, `RegistryCorrespondenceTest`, `OrganisationStructureTest`, `DashboardMetricsTest` | **PASS** for implemented scope — workflow routing is configurable and versioned; reserved-matter policy remains institutional (OD-20) |
| Backup/restore | NFR-09, AC-12 | `docs/RESTORE_DRILL_RUNBOOK.md` | manual | **Not demonstrated** (OD-14) |
| Load/performance | AC-14 | rate limiter, transactions, `lockForUpdate`, indices | `AuthenticationTest`, `LeaveWorkflowTest` | **Partial** — no load test |

## Cross-cutting requirements

| Requirement | Implementation | Evidence |
|---|---|---|
| Unique, stable staff identifier | `users.staff_number` unique; FK-linked `staff_profiles` | `ManualPersonnelEntryTest::test_duplicate_staff_number_is_rejected` |
| Private document storage | `private` disk, authorised streaming | `PlatformHardeningTest::test_private_document_vault_disk_is_configured_and_writable` |
| Safe upload/path handling | `extractZipSafely()` | `PlatformHardeningTest::test_zip_archive_with_path_traversal_entry_is_rejected` |
| Concurrency safety (references) | `ReferenceSequence::next()` | `PlatformHardeningTest::test_reference_sequence_increments_atomically_per_scope_and_year` |
| Business timezone | `config('app.timezone')` | `PlatformHardeningTest::test_institutional_business_timezone_is_africa_lagos` |
| Append-only audit, no secrets | `AuditLog` | `AuditTrailTest::test_audit_entries_are_append_only` |
| Duty-based authorisation (desk separation) | `DutyCatalog`, `duties`/`duty_user`, `User::hasDuty()` | `DutyAssignmentTest::test_two_registry_officers_can_hold_different_duties`, `test_payslip_release_requires_the_release_duty` |
| Versioned, configurable workflow | `WorkflowDefinition` → `WorkflowVersion` → `WorkflowStep`; `WorkflowEngine`; instances pin `workflow_version_id` | `RegistryCorrespondenceTest::test_a_routine_document_moves_from_draft_through_head_of_hr_and_registrar_to_issue`, `test_a_returned_document_can_be_corrected_and_resubmitted_into_the_same_workflow_version` |
| Task completion ≠ approval ≠ issuance | `AssignmentWorkflow`, `CorrespondenceService`, `work_assignments` vs `correspondence_documents` statuses | `RegistryAssignmentTest` (completion lifecycle), `RegistryCorrespondenceTest` (approval/issue separation) |
| Canonical departments and grouping | `DepartmentCatalog` (seven official codes), `departments.sort_order`, directory/import grouping | `DepartmentCatalogTest`, `PersonnelImportTest::test_import_preview_groups_rows_by_official_department_order` |
| Private correspondence attachments | `document_attachments`, `private` disk, SHA-256, audited upload/download, IDOR guard | `RegistryCorrespondenceTest::test_attachments_are_stored_privately_audited_and_idor_protected` |
| No account enumeration | generic reset response | `PasswordResetTest::test_requesting_a_reset_link_does_not_reveal_whether_an_account_exists` |

## Explicitly not built (P1 / deferred)

MFA (AUTH-10), SSO (AUTH-11), native mobile, payroll calculation, digital-signature semantics,
impersonation. See `docs/OPEN_DECISIONS.md` for owner and status of each open institutional
decision.
