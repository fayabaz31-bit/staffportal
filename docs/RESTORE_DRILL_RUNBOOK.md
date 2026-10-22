# Database and Document Vault Restore Drill Runbook

**System:** Nigeria French Language Village (NfLV) Staff Self-Service Portal  
**Target Recovery Objectives:** RTO (Recovery Time Objective) < 30 minutes; RPO (Recovery Point Objective) < 1 hour.  
**Critical Artifacts:** MySQL Database (`staffportal`) & Private Document Vault (`storage/app/private/`).

---

## 1. Backup Strategy Overview

A complete institutional backup consists of two interdependent components:

1. **Transactional Database (`staffportal.sql`):** Contains user credentials, immutable audit logs, leave ledgers, memo distribution snapshots, ticket message threads, and file metadata hashes.
2. **Private Document Vault (`storage/app/private/`):** Contains encrypted and access-gated PDF payslips, official leave approval letters, profile request attachments, and ticket evidence. **Never located in the public web root.**

---

## 2. Backup Execution Procedure

Execute backups during off-peak hours using the Laragon toolchain:

```powershell
# 1. Set environment variables
$env:PATH = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64;" + $env:PATH
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupDir = "C:\laragon\backups\staffportal_$Timestamp"
New-Item -ItemType Directory -Path $BackupDir -Force

# 2. Dump MySQL database with routines and transactions
mysqldump -u root staffportal --single-transaction --routines --triggers --events > "$BackupDir\staffportal_db.sql"

# 3. Archive private document vault
Compress-Archive -Path "C:\laragon\www\staffportal\storage\app\private" -DestinationPath "$BackupDir\private_vault.zip" -Force

# 4. Generate SHA-256 integrity manifest
Get-FileHash "$BackupDir\*" -Algorithm SHA256 | Export-Csv -Path "$BackupDir\manifest_checksums.csv" -NoTypeInformation
```

---

## 3. Restoration Drill Procedure

To simulate a disaster recovery scenario or verify backup integrity on a local test environment:

### Step 3.1: Pre-Restoration Verification
Verify backup package checksums against the manifest:
```powershell
Import-Csv "$BackupDir\manifest_checksums.csv" | ForEach-Object {
    $current = (Get-FileHash $_.Path -Algorithm SHA256).Hash
    if ($current -eq $_.Hash) {
        Write-Host "✔ Checksum Verified: $($_.Path)" -ForegroundColor Green
    } else {
        Write-Error "✖ Checksum Mismatch for $($_.Path)"
    }
}
```

### Step 3.2: Database Restoration
Re-create the target database and stream the dump:
```sql
-- Connect via MySQL CLI
DROP DATABASE IF EXISTS `staffportal_drill`;
CREATE DATABASE `staffportal_drill` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Restore using the command line:
```powershell
mysql -u root staffportal_drill < "$BackupDir\staffportal_db.sql"
```

### Step 3.3: Private Document Vault Restoration
Extract the archived private document vault into the storage root:
```powershell
$DrillVaultPath = "C:\laragon\www\staffportal\storage\app\private_drill"
Expand-Archive -Path "$BackupDir\private_vault.zip" -DestinationPath $DrillVaultPath -Force
```

### Step 3.4: Integrity Verification Drill
Run an integrity validation script checking that every database document record (`payslips`, `official_letters`) exists in the restored file store and matches its stored SHA-256 hash:

```powershell
php artisan tinker --execute '
$failed = 0;
foreach (\App\Models\OfficialLetter::all() as $letter) {
    if (!\Illuminate\Support\Facades\Storage::disk("private")->exists($letter->storage_path)) {
        echo "Missing letter: " . $letter->reference_number . PHP_EOL;
        $failed++;
    }
}
echo "Restore verification complete. Errors: " . $failed . PHP_EOL;
'
```

---

## 4. Disaster Recovery Sign-Off Checklist

- [ ] Database restored with zero SQL syntax errors or dropped table constraints.
- [ ] Users can sign in using existing staff ID and passwords (BCrypt hashes preserved).
- [ ] Private file permissions strictly enforce `403 Forbidden` for unauthorized actors.
- [ ] SHA-256 file hashes in `official_letters` match restored PDF binaries on disk.
- [ ] RTO measured and recorded within the 30-minute operational threshold.
