# Open Institutional Decisions

Living record of decisions the PRD leaves to the institution (per Master Prompt §A).
Until decided, affected capabilities stay **blocked or clearly marked UNCONFIGURED** in the product.
Status values: `open` | `proposed` | `decided` | `deferred`.

| # | Decision | Owner | Impact | Options / proposal | Status |
|---|----------|-------|--------|--------------------|--------|
| OD-01 | Official logo / crest / brand colours for NfLV | Institutional Comms / Executive Sponsor | Landing page, letters, PDF letterhead | (a) supply official asset; (b) approve text-based identity. Until supplied we render a clearly-marked **placeholder** monogram (PRD §3.1, Master Prompt §C — no invented crest) | open |
| OD-02 | Official staff-number format (regex/prefix) | Registry/HR | Unique ID validation, import mapping, login identifier | (a) follow existing HR register; (b) institute a new format. Dev uses `NFLV-\d{4}` DEMO format | open |
| OD-03 | Official institutional email domain & whether staff may log in with email | ICT | AUTH-01 login identifiers, password recovery channel | Provide domain (e.g. `*.nfobv.edu.ng` — placeholder only, unverified) and confirm email login | open |
| OD-04 | Leave categories, entitlement rules, weekend/holiday counting, carry-forward, cutoffs | Registry/HR + Executive | LEAVE-03/04/10/11: balances, duration, final approval block | (a) configure approved rules; (b) keep `UNCONFIGURED` and block final approval/balance deduction & official letter issuance | open — **blocking for production leave approval** |
| OD-05 | Final signing authority & signing convention for official letters | Executive Sponsor | DOC-05/08: "approved" status, signature block | (a) named signatories per doc type; (b) delegated alternates. **Never render unapproved signatures** (Master Prompt §F5) | open — **blocking for issued-letter finalisation** |
| OD-06 | Letter reference numbering convention | Registry | DOC-04: unique references | (a) `NfLV/REG/2026/0001`; (b) other institutional convention. Dev uses `DEV-REF-000001` | open |
| OD-07 | Payslip file format, naming convention & reconciliation/release procedure | Bursary + ICT | PAY-01/09: import adapter, manifest, release approval | (a) documented naming convention + ZIP; (b) validated manifest CSV + PDFs; confirm separation of duties for release officer | open — **blocking for batch import design (Phase 4)** |
| OD-08 | Payroll period convention (month/year fields, cutoff) | Bursary | PAY-04, unique index period+staff | (a) calendar month; (b) lunar/fiscal periods | open |
| OD-09 | SMTP host/credentials & email from-name/address | ICT | Notifications, password reset in dev | (a) institutional SMTP; (b) keep `log` mailer + Mailpit until provided. **No live mail from demo data** | open (dev uses `log`) |
| OD-10 | Approval workflow definitions per leave type & staff category (stages, delegated approvers, escalation SLAs) | HR + Management | LEAVE-07/08/12: routing engine configuration | Database-configurable stages are built; institution supplies the actual chains | open — **blocking for production routing** |
| OD-11 | Memo classification levels & who may approve each level | Executive + Registry | MEM-05/06: confidentiality and approval gating | Default proposal: `normal`, `restricted`, `confidential`; approver = Registry head / Director | open |
| OD-12 | Retention & disposal schedules (personnel, payslips, audit logs) | Data protection / legal | NFR-14, documents disposal | Propose: audit 7y, payslips 6y, personnel per statute — must be approved | open |
| OD-13 | Data-protection review under Nigeria Data Protection Act 2023 (notice text, DPIA, privacy notice content) | Legal / DPO | Privacy notice, terms links on landing page | Provide approved text; dev shows placeholder notice clearly marked as pending approval | open |
| OD-14 | Hosting, backup policy, RPO/RTO targets | ICT lead | NFR-09, restore drill acceptance | Define before pilot sign-off | open |
| OD-15 | MFA method & which roles are "privileged" | ICT | AUTH-10 (P1) | (a) TOTP app for all privileged roles; (b) email OTP. Proposal: TOTP for `ict_superadmin`, `institutional_superadmin`, `bursary_admin`, `hr_registry_admin`, `director` | proposed |
| OD-16 | Service-request categories, target units, SLA targets | Registry + Bursary | SRV-07 | Propose initial categories per unit; confirm SLA (e.g. 5 business days) | open |
| OD-17 | Whether institutional SSO (AUTH-11, P1) will be available | ICT | Post-pilot | Depends on IdP (e.g. institutional Azure AD/Keycloak) | deferred (P1) |
| OD-18 | Pilot group selection (20–30 users) & acceptance signatories | Product owner | Phase 7 rollout | Name users across ICT/Registry/Bursary/departments | open |

Deviation log: none. (No MySQL substitution approved; PostgreSQL 17.6 portable is used for local development per user approval on 14 Oct 2026.)
