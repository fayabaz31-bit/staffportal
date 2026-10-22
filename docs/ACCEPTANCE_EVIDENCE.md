# Acceptance Criteria Evidence Matrix (PRD §11: AC-01…AC-15)

This document maps each PRD acceptance criterion (**AC-01 through AC-15**) to executable
feature tests that exist in this repository. Every method named below was verified to exist
and pass at the time of writing. Where a criterion is only partially evidenced, or is covered
by a manual procedure rather than an automated test, that is stated explicitly rather than
reported as a full pass.

Last verified: **195 tests / 1172 assertions passing** (`php artisan test --compact`, 22 Oct 2026).

---

| ID | Test Scenario | Expected Result | Executable Evidence | Status |
|---|---|---|---|---|
| **AC-01** | A valid staff member logs in. | The correct personal dashboard is displayed for their assigned role. | `AuthenticationTest::test_active_user_can_sign_in_with_staff_number`<br>`AuthenticationTest::test_user_of_each_role_can_sign_in`<br>`AcceptanceCriteriaHardeningTest::test_ac01_valid_staff_login_displays_personal_dashboard` | **PASS** |
| **AC-02** | An unauthorised user attempts to open a private staff URL. | Access is denied without revealing protected content or metadata. | `AuthenticationTest::test_dashboard_redirects_guests_to_login`<br>`AcceptanceCriteriaHardeningTest::test_ac02_unauthorised_user_denied_private_urls`<br>`PayslipDistributionTest::test_unauthenticated_user_cannot_access_payslip_routes` | **PASS** |
| **AC-03** | Staff member A attempts to view staff member B's payslip. | The request is denied (anti-enumeration 404/403) and the download is blocked. | `PayslipDistributionTest::test_ac03_staff_a_cannot_download_staff_b_payslip`<br>`AcceptanceCriteriaHardeningTest::test_ac03_staff_cannot_download_another_staff_payslip` | **PASS** |
| **AC-04** | Registry publishes a memo to a selected department. | Only intended, authorised department recipients can access and view it. | `MemoCorrespondenceTest::test_department_recipients_are_snapshotted_at_publication`<br>`MemoCorrespondenceTest::test_only_the_snapshotted_recipient_can_view_a_published_memo`<br>`MemoCorrespondenceTest::test_recipient_can_acknowledge_a_memo_that_requires_it` | **PASS** |
| **AC-05** | Bursary uploads a batch containing a mismatched staff number. | The error is identified, quarantined, and the affected payslips are blocked from publication. | `PayslipDistributionTest::test_ac05_batch_with_mismatched_staff_number_is_quarantined_and_blocked`<br>`PayslipDistributionTest::test_batch_with_corrupt_non_pdf_file_is_quarantined`<br>`PayslipDistributionTest::test_bursary_admin_can_approve_and_publish_staged_batch` | **PASS** |
| **AC-06** | Staff submits a valid leave application. | The application is saved, pending days reserved, and routed to the correct configured approver. | `LeaveWorkflowTest::test_business_days_exclude_weekends_in_duration_calculation`<br>`LeaveWorkflowTest::test_multi_stage_approval_state_machine_advances_correctly` | **PASS** |
| **AC-07** | An approver rejects an application. | The decision, timestamp and required comments are retained; reserved balance is restored. | `LeaveWorkflowTest::test_rejection_restores_pending_days_and_saves_rejection_reason`<br>`LeaveWorkflowTest::test_staff_can_cancel_pending_leave_application_and_restore_balance` | **PASS** |
| **AC-08** | An authorised approver approves leave. | Produces official PDF letter, decrements balance, and stores a SHA-256 hash in private storage. | `LeaveWorkflowTest::test_multi_stage_approval_state_machine_advances_correctly`<br>`LeaveWorkflowTest::test_official_letter_can_be_downloaded_by_applicant_and_denied_to_other_staff` | **PASS** |
| **AC-09** | Staff submits a profile correction. | The request follows the required verification process before the official record changes. | `StaffProfileTest::test_official_field_change_is_recorded_as_pending_without_changing_profile`<br>`StaffProfileTest::test_hr_registry_approval_applies_the_requested_official_change`<br>`StaffProfileTest::test_hr_registry_rejection_keeps_the_canonical_record_unchanged` | **PASS** — Head of HR review does not mutate the record; the Registrar's final Registry approval does |
| **AC-10** | A privileged administrator changes a role assignment. | The change is authorised, recorded, and reflected immediately in subsequent permission checks. | `AccountLifecycleAndNotificationsTest::test_role_change_takes_immediate_effect_and_is_audited`<br>`AcceptanceCriteriaHardeningTest::test_ac10_role_assignment_takes_immediate_effect`<br>`DutyAssignmentTest::test_changing_a_role_resets_duties_to_the_new_baseline` | **PASS** |
| **AC-11** | Email delivery fails. | The underlying record remains intact and the notification failure can be retried. | `AccountLifecycleAndNotificationsTest::test_email_delivery_failure_retains_the_in_app_notification`<br>`AccountLifecycleAndNotificationsTest::test_in_app_notification_is_delivered_immediately_and_can_be_marked_read` | **PASS** |
| **AC-12** | A backup restoration test is performed. | Database and private document vault are restored within approved recovery objectives. | `docs/RESTORE_DRILL_RUNBOOK.md` — documented procedure. **Not executed in this environment**; RPO/RTO targets remain open (OD-14). | **DOCUMENTED — manual drill pending** |
| **AC-13** | Two authorised officers act on the same pending application. | Conflicting decisions are prevented via optimistic concurrency locking. | `LeaveWorkflowTest::test_concurrency_conflict_detected_and_aborted_when_version_mismatches` | **PASS** |
| **AC-14** | Application tested under load. | Database transactions, row-level locks, throttles, and indices protect performance. | `AuthenticationTest::test_repeated_failed_logins_are_rate_limited`<br>`LeaveWorkflowTest::test_concurrency_conflict_detected_and_aborted_when_version_mismatches`<br>Whole-suite runtime recorded below | **PARTIAL — throttling and locking proven; no dedicated load/soak test executed** |
| **AC-15** | A staff account is deactivated. | The user can no longer sign in, while historical records are retained. | `AccountLifecycleAndNotificationsTest::test_ict_can_deactivate_an_account_and_retain_historical_records`<br>`AuthenticationTest::test_suspended_user_cannot_sign_in`<br>`AcceptanceCriteriaHardeningTest::test_ac15_deactivated_account_blocked_from_login_while_history_retained` | **PASS** |

---

## Registry Department module — additional evidence (OD-20)

These scenarios are additional to AC-01…AC-15 and exercise the Registry pilot module. Every
method below exists in the repository and passed in the last full run.

| # | Scenario | Executable evidence | Status |
|---|---|---|---|
| R-01 | A staff-detail upload is previewed in the seven official department groups, regardless of CSV row order. | `PersonnelImportTest::test_import_preview_groups_rows_by_official_department_order` | **PASS** |
| R-02 | Extended personnel details (gender, grade, unit, first appointment, DOB, marital status, next of kin) persist on commit. | `PersonnelImportTest::test_extended_csv_import_persists_personnel_detail_fields` | **PASS** |
| R-03 | The staff directory can be filtered to one official department. | `PersonnelImportTest::test_directory_can_be_filtered_to_one_official_department` | **PASS** |
| R-04 | The canonical seven departments are created in fixed order; syncing upgrades a legacy code with the same name instead of duplicating it. | `DepartmentCatalogTest::test_the_canonical_departments_are_created_in_display_order`, `test_syncing_is_idempotent_and_upgrades_a_legacy_code_with_the_same_name` | **PASS** |
| R-05 | A supervisor assigns work; the officer acknowledges, progresses, submits and the supervisor completes the cycle. | `RegistryAssignmentTest::test_a_supervisor_can_assign_a_duty_to_a_report_and_the_officer_can_complete_the_cycle` | **PASS** |
| R-06 | An assignee cannot close their own assignment (task completion is not self-approved). | `RegistryAssignmentTest::test_an_assignee_cannot_close_their_own_duty` | **PASS** |
| R-07 | Returning work requires a comment and creates a new submission version. | `RegistryAssignmentTest::test_a_return_requires_a_comment_and_creates_a_new_version_for_resubmission` | **PASS** |
| R-08 | Unrelated officers cannot view or act on another officer's assignment. | `RegistryAssignmentTest::test_unrelated_officers_cannot_view_or_act_on_a_duty` | **PASS** |
| R-09 | Work can only be assigned to someone the actor actually supervises. | `RegistryAssignmentTest::test_an_officer_cannot_assign_work_to_someone_they_do_not_supervise` | **PASS** |
| R-10 | Assignment attachments are private and downloads are audited. | `RegistryAssignmentTest::test_attachments_are_private_and_downloads_are_audited` | **PASS** |
| R-11 | A configured reporting line can authorise the Director to assign work without holding a Registry desk duty. | `RegistryAssignmentTest::test_a_configured_reporting_line_lets_a_director_assign_without_a_registry_duty` | **PASS** |
| R-12 | The Head of HR stage forwards but does not mutate the official record; the Registrar's approval applies it. | `StaffProfileTest::test_hr_registry_approval_applies_the_requested_official_change` | **PASS** |
| R-13 | Routine correspondence moves draft → Head of HR → Registrar → approved, and only then may be issued. | `RegistryCorrespondenceTest::test_a_routine_document_moves_from_draft_through_head_of_hr_and_registrar_to_issue` | **PASS** |
| R-14 | Director-reserved correspondence cannot be issued before the Director decides. | `RegistryCorrespondenceTest::test_promotion_correspondence_cannot_be_issued_before_the_director_decides` | **PASS** |
| R-15 | An officer outside the current stage cannot view or decide confidential correspondence. | `RegistryCorrespondenceTest::test_an_officer_outside_the_stage_cannot_view_or_decide_confidential_correspondence` | **PASS** |
| R-16 | A returned document is corrected and resubmitted into the same pinned workflow version. | `RegistryCorrespondenceTest::test_a_returned_document_can_be_corrected_and_resubmitted_into_the_same_workflow_version` | **PASS** |
| R-17 | Correspondence attachments are private, audited, originator-only and IDOR-protected. | `RegistryCorrespondenceTest::test_attachments_are_stored_privately_audited_and_idor_protected`, `test_only_the_originator_can_add_an_attachment` | **PASS** |
| R-18 | `ict_superadmin` has no personnel-correspondence authority. | `RegistryCorrespondenceTest::test_an_ict_technical_administrator_cannot_reach_personnel_correspondence`, `OrganisationStructureTest::test_a_technical_administrator_cannot_create_registry_correspondence` | **PASS** |
| R-19 | Reporting lines reject self-reference/cycles/overlap; delegations are time-bounded and revocable; non-managers cannot mutate the hierarchy. | `OrganisationStructureTest::test_overlapping_reporting_lines_are_rejected`, `test_a_time_bounded_delegation_grants_the_scope_only_while_active`, `test_only_authorised_administrators_can_view_or_change_the_hierarchy` | **PASS** |
| R-20 | The Registry dashboard follows the configured approval stage per desk and counts Director-reserved matters only where routed. | `DashboardMetricsTest::test_the_registry_dashboard_follows_the_configured_approval_stage_per_desk`, `test_the_director_dashboard_counts_only_reserved_matters` | **PASS** |

---

- **AC-11** — In-app notices are written synchronously to the `notifications` table, so they are
  visible even when no queue worker is running. The email is dispatched through the queue
  (`PortalEmailNotification implements ShouldQueue`). The automated test injects a failing mail
  transport and asserts that the authoritative in-app record still exists afterwards. A failed
  email lands in `failed_jobs` for retry; no data is deleted.
- **AC-12** — Only a written runbook exists. The restore drill itself is an operational activity
  that requires a disposable database and private-vault copy, neither of which is provisioned in
  this development environment. Treat AC-12 as **not demonstrated** until the drill is executed
  and signed off.

## Verification Execution Summary

Run the full automated suite:

```powershell
php artisan test --compact
```

Current result: **195 tests / 1172 assertions / 0 failures** (approximately 67 s on the developer
Windows machine with MySQL 8.4.3). PHPUnit reports one test as *risky* (a path without its own
assertion); the run still exits successfully. AC-14's load characteristic is inferred from row
locking, transactions and rate limiting; it is not a substitute for a real load test.
