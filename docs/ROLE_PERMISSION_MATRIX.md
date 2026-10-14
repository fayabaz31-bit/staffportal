# Role / Permission Matrix (PRD §2, §2.1, §4)

Permissions are implemented as named **abilities** checked by policies/gates on the server.
Scope column: `own` = record owner only, `assigned` = workflow-assigned cases, `dept` = own department, `institution` = all in authorised domain.
Hidden navigation is never a control — every action below is server-enforced and negatively tested (Master Prompt §E).

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
| `payslip.batch.approve-release` | ✖ | ✖ | ✔ authorised release officer (separation of duties where configured) | ✖ | ✖ | ✖ | ✖ | ✖ |
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
| `user.account.activate/suspend/deactivate` | ✖ | ✖ | ✖ | ✖ | ✖ | ✔ | ✖ | ✖ |
| `user.role.assign` (audited, immediate effect) | ✖ | ✖ | ✖ | ✖ | ✖ | ✖ | ✔ | ✖ |
| `system.settings.manage` | ✖ | ✖ | ✖ | ✖ | ✖ | ✔ technical | ✔ policy | ✖ |
| `audit.log.view` | ✖ | ✖ | ✖ | ✖ | ✖ | ✔ technical scope (no sensitive payload content) | ✔ | ✔ scoped read-only |
| `report.run` / `report.export` (audited) | ✖ | ✔ personnel scope | ✔ batch recon scope | ✔ dept scope | ✔ delegated scope | ✖ (no business data export) | ✔ authorised | ✔ read-only scoped |
| **Notifications** |
| `notification.inbox.view.own` | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ | ✔ |

### Confidentiality rules encoded in policies (PRD §2.1 + Master Prompt §E)
1. **Own-records rule:** any private artefact (profile, payslip, leave, letter) is loaded through an ownership/authorised-recipient policy — never by raw ID.
2. **Registry ≠ payroll:** `hr_registry_admin` has zero `payslip.*` abilities.
3. **ICT ≠ business authority:** `ict_superadmin` manages accounts/settings/ops logs; cannot approve leave, publish memos, or open private documents/contents.
4. **Bursary isolation:** confidential salary enquiries are visible only with an explicit grant; no automatic cross-unit view.
5. **Approver validity:** `leave.decide` etc. require the user to be an *active, current assignee* of the workflow stage (checked with row locks to stop double decisions).
6. **Supersadmins are scoped:** `institutional_superadmin` grants are audited and permission-scoped; no blanket "bypass all" gate exists in code.
7. **Auditor:** read-only abilities only, scoped to explicitly authorised records; export actions are logged.
8. **Impersonation:** not implemented (not in MVP; Master Prompt §E).

### Account lifecycle states (AUTH-07)
`active` → may authenticate. `suspended` / `deactivated` → authentication blocked, historical records preserved, status change audited (AC-15).
