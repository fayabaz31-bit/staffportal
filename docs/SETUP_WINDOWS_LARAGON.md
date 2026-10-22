# Local Windows & Laragon Developer Setup Guide

This guide describes how to run and develop the **Nigeria French Language Village (NfLV) Staff Self-Service Portal** using the native local Laragon environment.

---

## 1. Environment Prerequisites

The application was built and validated against the following local environment:
- **Operating System:** Windows 10/11 x64
- **Web Server Suite:** Laragon Full
- **PHP Version:** `PHP 8.3.33` (VS16 x64 Thread Safe) located at `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64`
- **Database Engine:** `MySQL 8.4.3` located at `C:\laragon\bin\mysql\mysql-8.4.3-winx64`
- **Node.js & npm:** `Node.js v22` located at `C:\laragon\bin\nodejs\node-v22`
- **Dependency Manager:** Composer 2.8+ located at `C:\laragon\bin\composer`

Required PHP extensions enabled in `php.ini`:
- `pdo_mysql`
- `mbstring`
- `openssl`
- `fileinfo`
- `curl`
- `gd`
- `zip`
- `xml`

---

## 2. Directory & Path Setup

Open PowerShell and add the Laragon toolchains to your session `PATH`:

```powershell
$env:PATH = "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64;C:\laragon\bin\composer;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\git\bin;" + $env:PATH
cd c:\laragon\www\staffportal
```

---

## 3. Database Configuration

Ensure MySQL is running in Laragon (Port 3306). Create the development and test databases:

```sql
CREATE DATABASE IF NOT EXISTS `staffportal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `staffportal_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update your `.env` configuration:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=staffportal
DB_USERNAME=root
DB_PASSWORD=

# Session and Private Storage Drivers
SESSION_DRIVER=database
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
```

---

## 4. Application Installation & Seeding

Run database migrations and seed synthetic demo data:

```powershell
# Install PHP dependencies
composer install

# Generate application key
php artisan key:generate

# Execute database migrations
php artisan migrate

# Seed demo users and categories across all 8 roles
php artisan db:seed
```

---

## 5. Frontend Asset Compilation

Install Node dependencies and compile the Tailwind CSS & Vite assets:

```powershell
npm install
npm run build
```

For active frontend development with Hot Module Replacement (HMR):
```powershell
npm run dev
```

### Background queue worker (required for email notifications)

In-app notifications are written to the database immediately, but notification **emails** are
queued (`PortalEmailNotification implements ShouldQueue`). With `QUEUE_CONNECTION=database`,
run a worker so queued mail is actually delivered:

```powershell
php artisan queue:work
```

In local development keep `MAIL_MAILER=log` (messages are written to `storage/logs/laravel.log`)
until institutional SMTP is provided (OD-09). Without a running worker the secure in-app inbox
still works; any pending email simply waits in the `jobs` table and retries later.

---

## 6. Running the Test Suite

Run the complete automated test suite on the isolated `staffportal_test` database:

```powershell
php artisan test
```

To run individual domain feature tests:
```powershell
php artisan test --filter=AuthenticationTest
php artisan test --filter=PersonnelImportTest
php artisan test --filter=DepartmentCatalogTest
php artisan test --filter=RegistryAssignmentTest
php artisan test --filter=RegistryCorrespondenceTest
php artisan test --filter=OrganisationStructureTest
php artisan test --filter=MemoCorrespondenceTest
php artisan test --filter=PayslipDistributionTest
php artisan test --filter=LeaveWorkflowTest
php artisan test --filter=ServiceRequestAndReportingTest
php artisan test --filter=AcceptanceCriteriaHardeningTest
```

---

## 7. Demo Accounts for Manual Verification

The database seeder syncs the seven official departments and provisions demo accounts across the 8 role domains plus two Registry pilot desks (password: `password`):

| Role | Staff Number | Email | Primary Capability |
|---|---|---|---|
| **Staff Member** | `NFLV-0001` | `demo.staff@nfvl.test` | Profile, Memos, Payslips, Leave, Service Requests |
| **Head of HR (Registry)** | `NFLV-0002` | `demo.hr.registry.admin@nfvl.test` | Personnel Directory, CSV Import, Profile Approvals, Leave Verify, Registry Desk, **Head of HR correspondence review** |
| **Bursary Admin** | `NFLV-0003` | `demo.bursary.admin@nfvl.test` | Payslip ZIP Batches, Reconciliation, Confidential Salary Enquiries |
| **Head of Department** | `NFLV-0004` | `demo.head.of.department@nfvl.test` | Department Leave Recommendation, Department Reports |
| **Director** | `NFLV-0005` | `demo.director@nfvl.test` | Final Leave Approvals, Official PDF Letters, Institutional Oversight, **Director escalation for reserved Registry matters** |
| **ICT Superadmin** | `NFLV-0006` | `demo.ict.superadmin@nfvl.test` | Account Lifecycle, Technical Configuration, ICT Helpdesk |
| **Institutional Superadmin** | `NFLV-0007` | `demo.institutional.superadmin@nfvl.test` | Audited Governance Oversight across all units |
| **Auditor / Read-Only** | `NFLV-0008` | `demo.auditor.readonly@nfvl.test` | Scoped Read-Only Reporting and Export Audits |
| **Registrar (Registry pilot)** | `NFLV-0009` | `demo.registrar@nfvl.test` | **Final Registry approval**, Registry correspondence issuance, Registry duty assignment |
| **Personnel Officer (Registry pilot)** | `NFLV-0010` | `demo.personnel.officer@nfvl.test` | Baseline Registry desk work and assigned duties only |

The seeder also creates the units `REG-PERSONNEL` (Head of HR), `REG-RECORDS` (Registrar) and
`DIR-EXECUTIVE` (Director), reporting lines, and the three default correspondence workflow
definitions. Chain duties (`registry.head_review`, `registry.registrar_approve`,
`registry.director_escalation`) are explicit grants and are **not** part of the
`hr_registry_admin` baseline; a fresh account at that role starts as a Registry officer.
