<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payslip;
use App\Models\PayslipBatch;
use App\Models\User;
use App\Notifications\PortalNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class BursaryPayslipBatchController extends Controller
{
    public function index(): View
    {
        $batches = PayslipBatch::query()
            ->with(['uploader', 'approver'])
            ->latest()
            ->paginate(15);

        return view('bursary.payslips.index', [
            'batches' => $batches,
        ]);
    }

    public function create(): View
    {
        return view('bursary.payslips.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'payroll_period' => ['required', 'regex:/^\d{4}-(?:0[1-9]|1[0-2])$/'],
            'batch_file' => ['required', 'file', 'max:51200'],
        ]);

        $period = $request->input('payroll_period');
        $uploadedFile = $request->file('batch_file');
        $originalFilename = $uploadedFile->getClientOriginalName();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if (! in_array($extension, ['zip', 'pdf'], true)) {
            return back()->withInput()->withErrors([
                'batch_file' => 'The uploaded file must be a ZIP archive of PDF payslips or a single PDF file.',
            ]);
        }

        $stagingUuid = (string) Str::uuid();
        $stagingDir = 'staging_payslips/'.$stagingUuid;
        $stagingFullPath = storage_path('app/private/'.$stagingDir);
        File::ensureDirectoryExists($stagingFullPath.'/extracted');

        $extractedFiles = [];

        if ($extension === 'zip') {
            $zip = new ZipArchive;
            $zipPath = $uploadedFile->getRealPath();

            if ($zip->open($zipPath) !== true) {
                return back()->withInput()->withErrors([
                    'batch_file' => 'The uploaded ZIP archive is corrupted or could not be read.',
                ]);
            }

            $extraction = $this->extractZipSafely($zip, $stagingFullPath.'/extracted');
            $zip->close();

            if (isset($extraction['error'])) {
                File::deleteDirectory($stagingFullPath);

                return back()->withInput()->withErrors([
                    'batch_file' => $extraction['error'],
                ]);
            }

            $allFiles = File::allFiles($stagingFullPath.'/extracted');
            foreach ($allFiles as $file) {
                $filename = $file->getFilename();
                // Skip system and hidden files
                if (str_starts_with($filename, '.') || str_contains($file->getPathname(), '__MACOSX')) {
                    continue;
                }
                if (strtolower($file->getExtension()) === 'pdf') {
                    $extractedFiles[] = $file;
                }
            }

            if (empty($extractedFiles)) {
                File::deleteDirectory($stagingFullPath);

                return back()->withInput()->withErrors([
                    'batch_file' => 'The ZIP archive does not contain any PDF payslip files.',
                ]);
            }
        } else {
            // Single PDF file
            $destPath = $stagingFullPath.'/extracted/'.$originalFilename;
            File::copy($uploadedFile->getRealPath(), $destPath);
            $extractedFiles[] = new \SplFileInfo($destPath);
        }

        // Process and dry-run validate every file
        $matched = [];
        $errors = [];
        $seenStaffNumbers = [];

        foreach ($extractedFiles as $file) {
            $filename = $file->getFilename();
            $filePath = $file->getRealPath();
            $fileSize = File::size($filePath);

            // Validate PDF header magic bytes
            $handle = fopen($filePath, 'rb');
            $header = $handle ? fread($handle, 5) : '';
            if ($handle) {
                fclose($handle);
            }

            if (! str_starts_with($header, '%PDF-')) {
                $errors[] = [
                    'filename' => $filename,
                    'error' => 'File is corrupted or not a valid PDF document (missing %PDF- header).',
                ];

                continue;
            }

            // Extract staff number from filename: e.g. "NFLV-0001.pdf" or "NFLV-0001_2026-09.pdf"
            if (! preg_match('/^([A-Za-z0-9-]+?)(?:_\d{4}-\d{2})?\.pdf$/i', $filename, $matches)) {
                $errors[] = [
                    'filename' => $filename,
                    'error' => "Filename could not be parsed. Expected '<STAFF_NUMBER>.pdf' or '<STAFF_NUMBER>_{$period}.pdf'.",
                ];

                continue;
            }

            $staffNumber = strtoupper(trim($matches[1]));

            // Check duplicate in same batch
            if (isset($seenStaffNumbers[$staffNumber])) {
                $errors[] = [
                    'filename' => $filename,
                    'error' => "Duplicate payslip for staff number {$staffNumber} found in this batch (conflicts with {$seenStaffNumbers[$staffNumber]}).",
                ];

                continue;
            }
            $seenStaffNumbers[$staffNumber] = $filename;

            // Match against user records
            $user = User::query()->where('staff_number', $staffNumber)->first();

            if (! $user) {
                $errors[] = [
                    'filename' => $filename,
                    'error' => "No registered staff member found with staff number '{$staffNumber}'.",
                ];

                continue;
            }

            if ($user->status !== 'active') {
                $errors[] = [
                    'filename' => $filename,
                    'error' => "Staff member '{$staffNumber}' is {$user->status}; cannot allocate payslip to inactive account.",
                ];

                continue;
            }

            // Check if user already has a published payslip for this period
            $existingPublished = Payslip::query()
                ->where('user_id', $user->id)
                ->where('payroll_period', $period)
                ->where('status', Payslip::STATUS_PUBLISHED)
                ->exists();

            if ($existingPublished) {
                $errors[] = [
                    'filename' => $filename,
                    'error' => "Staff member '{$staffNumber}' already has an active published payslip for period {$period}. Withdraw previous payslip first to issue a replacement.",
                ];

                continue;
            }

            $fileHash = hash_file('sha256', $filePath);
            $relativePath = $stagingDir.'/extracted/'.$file->getBasename();

            $matched[] = [
                'user_id' => $user->id,
                'staff_number' => $user->staff_number,
                'name' => $user->name,
                'department' => $user->staffProfile?->department?->name ?? 'Unassigned',
                'filename' => $filename,
                'staging_path' => $relativePath,
                'size' => $fileSize,
                'hash' => $fileHash,
            ];
        }

        $totalFiles = count($extractedFiles);
        $matchedCount = count($matched);
        $errorCount = count($errors);

        $status = $errorCount > 0 ? PayslipBatch::STATUS_QUARANTINED : PayslipBatch::STATUS_STAGED;

        $batch = PayslipBatch::create([
            'uploaded_by' => $request->user()->id,
            'payroll_period' => $period,
            'original_filename' => $originalFilename,
            'status' => $status,
            'total_files' => $totalFiles,
            'matched_files' => $matchedCount,
            'error_count' => $errorCount,
            'reconciliation_summary' => [
                'matched' => $matched,
                'errors' => $errors,
                'staging_dir' => $stagingDir,
            ],
        ]);

        if ($status === PayslipBatch::STATUS_QUARANTINED) {
            return redirect()
                ->route('bursary.payslips.show', $batch)
                ->with('warning', "Batch quarantined. Found {$errorCount} error(s). All material errors must be resolved before publication.");
        }

        return redirect()
            ->route('bursary.payslips.show', $batch)
            ->with('status', "Batch successfully staged with {$matchedCount} validated payslip(s). Review reconciliation summary and approve release.");
    }

    public function show(PayslipBatch $batch): View
    {
        $batch->load(['uploader', 'approver', 'publisher', 'withdrawnBy']);

        return view('bursary.payslips.show', [
            'batch' => $batch,
        ]);
    }

    public function publish(Request $request, PayslipBatch $batch): RedirectResponse
    {
        // Separation of duties: the release officer must not be the uploader.
        if ($batch->uploaded_by === $request->user()->id) {
            return back()->with('error', 'Separation of duties: the officer who uploaded this batch cannot release it. A different authorised approver must approve the release.');
        }

        // Enforce PRD quarantine rule: cannot release quarantined batch or batch with errors
        if ($batch->error_count > 0 || $batch->status === PayslipBatch::STATUS_QUARANTINED) {
            return back()->with('error', 'Quarantined batches with unresolved errors cannot be released.');
        }

        if ($batch->status !== PayslipBatch::STATUS_STAGED) {
            return back()->with('error', 'This batch cannot be published because it is in status: '.$batch->status);
        }

        $summary = $batch->reconciliation_summary ?? [];
        $matchedList = $summary['matched'] ?? [];

        if (empty($matchedList)) {
            return back()->with('error', 'No validated payslips found in this batch to publish.');
        }

        DB::transaction(function () use ($batch, $matchedList, $request, $summary) {
            $payrollPeriod = $batch->payroll_period;
            $destinationDir = 'payslips/'.$payrollPeriod;

            foreach ($matchedList as $item) {
                $stagingRelative = $item['staging_path'];
                $stagingFull = storage_path('app/private/'.$stagingRelative);

                $finalFilename = $item['hash'].'.pdf';
                $permanentStoragePath = $destinationDir.'/'.$finalFilename;

                // Store file in permanent private storage
                if (File::exists($stagingFull)) {
                    Storage::disk('local')->put($permanentStoragePath, File::get($stagingFull));
                    @File::delete($stagingFull);
                }

                Payslip::create([
                    'payslip_batch_id' => $batch->id,
                    'user_id' => $item['user_id'],
                    'staff_number' => $item['staff_number'],
                    'payroll_period' => $payrollPeriod,
                    'original_filename' => $item['filename'],
                    'storage_path' => $permanentStoragePath,
                    'file_hash' => $item['hash'],
                    'file_size' => $item['size'],
                    'status' => Payslip::STATUS_PUBLISHED,
                    'published_at' => now(),
                ]);
            }

            $batch->update([
                'status' => PayslipBatch::STATUS_PUBLISHED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'published_by' => $request->user()->id,
                'published_at' => now(),
            ]);

            // Clean up staging directory
            if (! empty($summary['staging_dir'])) {
                File::deleteDirectory(storage_path('app/private/'.$summary['staging_dir']));
            }
        });

        $affectedUserIds = collect($matchedList)->pluck('user_id')->unique()->all();

        PortalNotifier::sendMany(
            User::query()->whereIn('id', $affectedUserIds)->get(),
            'New payslip available',
            'Your payslip for '.$batch->formattedPeriod().' is now available in the portal.',
            route('payslips.index'),
            'payslip',
        );

        AuditLog::record('payslip_batch.published', $batch, [
            'payroll_period' => $batch->payroll_period,
            'published_payslips' => count($matchedList),
        ]);

        return redirect()
            ->route('bursary.payslips.show', $batch)
            ->with('status', "Successfully approved and published {$batch->matched_files} payslip(s) for {$batch->formattedPeriod()}.");
    }

    public function withdraw(Request $request, PayslipBatch $batch): RedirectResponse
    {
        $request->validate([
            'withdrawal_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        if ($batch->status !== PayslipBatch::STATUS_PUBLISHED) {
            return back()->with('error', 'Only active published batches can be withdrawn.');
        }

        $reason = $request->input('withdrawal_reason');

        DB::transaction(function () use ($batch, $request, $reason) {
            $batch->update([
                'status' => PayslipBatch::STATUS_WITHDRAWN,
                'withdrawn_by' => $request->user()->id,
                'withdrawn_at' => now(),
                'withdrawal_reason' => $reason,
            ]);

            $batch->payslips()->update([
                'status' => Payslip::STATUS_WITHDRAWN,
                'withdrawn_by' => $request->user()->id,
                'withdrawn_at' => now(),
                'withdrawal_reason' => $reason,
            ]);
        });

        PortalNotifier::sendMany(
            User::query()->whereIn('id', $batch->payslips()->pluck('user_id'))->get(),
            'Payslip withdrawn',
            'A payslip previously issued to you has been withdrawn and is no longer available.',
            route('payslips.index'),
            'payslip',
        );

        AuditLog::record('payslip_batch.withdrawn', $batch, [
            'payroll_period' => $batch->payroll_period,
            'reason' => $reason,
        ]);

        return redirect()
            ->route('bursary.payslips.show', $batch)
            ->with('status', 'Batch has been withdrawn. Associated payslips are no longer accessible to staff.');
    }

    public function destroy(PayslipBatch $batch): RedirectResponse
    {
        if ($batch->status === PayslipBatch::STATUS_PUBLISHED) {
            return back()->with('error', 'Published batches cannot be discarded. You must withdraw the batch with a documented reason.');
        }

        $summary = $batch->reconciliation_summary ?? [];
        if (! empty($summary['staging_dir'])) {
            File::deleteDirectory(storage_path('app/private/'.$summary['staging_dir']));
        }

        $batch->delete();

        return redirect()
            ->route('bursary.payslips.index')
            ->with('status', 'Batch has been discarded.');
    }

    /**
     * Safely extract a ZIP archive into a flat destination directory.
     *
     * Prevents Zip-Slip path traversal by rejecting absolute paths, drive
     * letters and parent-directory segments, and guards against decompression
     * bombs via entry-count and total-uncompressed-size limits. Files are
     * written using their base name so no entry can escape the destination.
     *
     * @return array{error?: string}
     */
    private function extractZipSafely(ZipArchive $zip, string $destination): array
    {
        File::ensureDirectoryExists($destination);

        $maxEntries = 2000;
        $maxUncompressedBytes = 512 * 1024 * 1024;
        $uncompressedBytes = 0;
        $written = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = (string) $zip->getNameIndex($index);
            $normalized = str_replace('\\', '/', $entryName);

            if (
                $entryName === ''
                || str_ends_with($normalized, '/')
                || str_starts_with($normalized, '__MACOSX/')
                || str_contains($normalized, '/__MACOSX/')
            ) {
                continue;
            }

            if (
                str_contains($normalized, "\0")
                || str_starts_with($normalized, '/')
                || preg_match('#^[A-Za-z]:#', $normalized) === 1
                || in_array('..', explode('/', $normalized), true)
            ) {
                return ['error' => 'The ZIP archive contains an unsafe file path and was rejected.'];
            }

            if (count($written) >= $maxEntries) {
                return ['error' => 'The ZIP archive contains too many files to process safely.'];
            }

            $stat = $zip->statIndex($index);
            $uncompressedBytes += (int) ($stat['size'] ?? 0);

            if ($uncompressedBytes > $maxUncompressedBytes) {
                return ['error' => 'The ZIP archive expands beyond the permitted size and was rejected.'];
            }

            $safeName = basename($normalized);

            if (str_starts_with($safeName, '.')) {
                continue;
            }

            if (isset($written[$safeName])) {
                return ['error' => "The ZIP archive contains duplicate file names ('{$safeName}') and was rejected."];
            }

            $contents = $zip->getFromIndex($index);
            if ($contents === false) {
                return ['error' => "The ZIP archive entry '{$safeName}' could not be read."];
            }

            File::put($destination.'/'.$safeName, $contents);
            $written[$safeName] = true;
        }

        return [];
    }
}
