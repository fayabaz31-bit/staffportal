<?php

namespace App\Http\Controllers;

use App\DepartmentCatalog;
use App\Models\Department;
use App\Models\PersonnelImportBatch;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonnelImportController extends Controller
{
    /**
     * Columns every upload must carry. Additional personnel detail columns are
     * accepted when present, so an older nine-column file still imports while a
     * full staff-detail file carries the complete record.
     */
    private const REQUIRED_HEADERS = [
        'staff_number',
        'name',
        'email',
        'department_code',
        'designation',
        'employment_status',
        'phone_number',
        'personal_email',
        'residential_address',
    ];

    /**
     * Optional personnel-detail columns.
     */
    private const OPTIONAL_HEADERS = [
        'gender',
        'grade_level',
        'unit',
        'date_of_first_appointment',
        'date_of_birth',
        'marital_status',
        'next_of_kin',
        'next_of_kin_phone',
    ];

    private const MAX_ROWS = 500;

    public function create(): View
    {
        return view('registry.personnel-imports.create');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [...self::REQUIRED_HEADERS, ...self::OPTIONAL_HEADERS], ',', '"', '\\', "\r\n");
            fclose($handle);
        }, 'nfvl-personnel-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'extensions:csv',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
                'max:2048',
            ],
        ]);

        $rows = $this->parseAndValidate($validated['file']);
        $validRowCount = count(array_filter($rows, fn (array $row): bool => $row['status'] === 'valid'));
        $errorRowCount = count($rows) - $validRowCount;

        $batch = PersonnelImportBatch::create([
            'uploaded_by' => $request->user()->id,
            'original_filename' => basename($validated['file']->getClientOriginalName()),
            'status' => $errorRowCount === 0
                ? PersonnelImportBatch::STATUS_PREVIEW
                : PersonnelImportBatch::STATUS_NEEDS_REVIEW,
            'row_count' => count($rows),
            'valid_row_count' => $validRowCount,
            'error_row_count' => $errorRowCount,
            'preview_rows' => $rows,
        ]);

        return redirect()->route('registry.personnel-imports.show', $batch);
    }

    public function show(Request $request, PersonnelImportBatch $personnelImportBatch): View
    {
        abort_unless($personnelImportBatch->uploaded_by === $request->user()->id, 404);

        $rowsByDepartment = collect($personnelImportBatch->preview_rows)
            ->groupBy(fn (array $row): string => $row['data']['department_code'] ?? 'UNASSIGNED');

        $orderedCodes = [
            ...array_keys(DepartmentCatalog::all()),
            ...$rowsByDepartment->keys()->reject(
                fn (string $code): bool => array_key_exists($code, DepartmentCatalog::all()),
            )->all(),
        ];

        $groupedRows = collect($orderedCodes)
            ->mapWithKeys(fn (string $code): array => [$code => $rowsByDepartment->get($code, collect())])
            ->filter(fn ($rows): bool => $rows->isNotEmpty());

        return view('registry.personnel-imports.show', [
            'batch' => $personnelImportBatch,
            'groupedRows' => $groupedRows,
        ]);
    }

    public function commit(Request $request, PersonnelImportBatch $personnelImportBatch): RedirectResponse
    {
        try {
            $result = DB::transaction(function () use ($request, $personnelImportBatch): array {
                $batch = PersonnelImportBatch::query()
                    ->lockForUpdate()
                    ->findOrFail($personnelImportBatch->id);

                abort_unless($batch->uploaded_by === $request->user()->id, 404);

                if ($batch->status !== PersonnelImportBatch::STATUS_PREVIEW || $batch->error_row_count > 0) {
                    throw ValidationException::withMessages([
                        'import' => 'This preview contains errors or has already been committed. Upload a corrected file.',
                    ]);
                }

                $rows = $batch->preview_rows;
                $staffNumbers = [];
                $emails = [];

                foreach ($rows as $index => $row) {
                    $data = $row['data'];
                    $errors = [];

                    if (User::query()->where('staff_number', $data['staff_number'])->exists()
                        || isset($staffNumbers[$data['staff_number']])) {
                        $errors[] = 'Staff number now exists or is duplicated in this import.';
                    }

                    if ($data['email'] !== null && (User::query()->where('email', $data['email'])->exists()
                        || isset($emails[$data['email']]))) {
                        $errors[] = 'Institutional email now exists or is duplicated in this import.';
                    }

                    if (! Department::query()->where('code', $data['department_code'])->where('is_active', true)->exists()) {
                        $errors[] = 'Department is no longer active.';
                    }

                    if ($errors !== []) {
                        $rows[$index]['errors'] = $errors;
                        $rows[$index]['status'] = 'invalid';
                    }

                    $staffNumbers[$data['staff_number']] = true;
                    if ($data['email'] !== null) {
                        $emails[$data['email']] = true;
                    }
                }

                $errorRowCount = count(array_filter($rows, fn (array $row): bool => $row['status'] !== 'valid'));

                if ($errorRowCount > 0) {
                    $batch->update([
                        'status' => PersonnelImportBatch::STATUS_NEEDS_REVIEW,
                        'valid_row_count' => count($rows) - $errorRowCount,
                        'error_row_count' => $errorRowCount,
                        'preview_rows' => $rows,
                    ]);

                    return ['committed' => false, 'count' => 0];
                }

                foreach ($rows as $row) {
                    $data = $row['data'];
                    $department = Department::query()->where('code', $data['department_code'])->firstOrFail();
                    $user = User::create([
                        'staff_number' => $data['staff_number'],
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Str::random(64),
                        'status' => 'suspended',
                        'role' => UserRole::Staff,
                    ]);

                    $user->staffProfile()->create([
                        'department_id' => $department->id,
                        'designation' => $data['designation'],
                        'gender' => $data['gender'] ?? null,
                        'grade_level' => $data['grade_level'] ?? null,
                        'unit' => $data['unit'] ?? null,
                        'date_of_first_appointment' => $data['date_of_first_appointment'] ?? null,
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'marital_status' => $data['marital_status'] ?? null,
                        'next_of_kin' => $data['next_of_kin'] ?? null,
                        'next_of_kin_phone' => $data['next_of_kin_phone'] ?? null,
                        'employment_status' => $data['employment_status'],
                        'phone_number' => $data['phone_number'],
                        'personal_email' => $data['personal_email'],
                        'residential_address' => $data['residential_address'],
                    ]);
                }

                $batch->update([
                    'status' => PersonnelImportBatch::STATUS_COMMITTED,
                    'committed_at' => now(),
                ]);

                return ['committed' => true, 'count' => count($rows)];
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1062) {
                throw $exception;
            }

            return redirect()->route('registry.personnel-imports.show', $personnelImportBatch)
                ->withErrors(['import' => 'The data changed after preview. Create a fresh preview before committing.']);
        }

        if (! $result['committed']) {
            return redirect()->route('registry.personnel-imports.show', $personnelImportBatch)
                ->withErrors(['import' => 'Records changed after preview. Review the updated row errors before committing.']);
        }

        return redirect()->route('registry.personnel.index')
            ->with('status', $result['count'].' staff accounts imported as suspended accounts.');
    }

    /**
     * @return array<int, array{line_number: int, status: string, data: array<string, mixed>, errors: array<int, string>}>
     */
    private function parseAndValidate(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'The CSV file could not be read.']);
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '\\');

            if ($headers === false) {
                throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
            }

            $headers = array_map(fn ($header): string => strtolower(trim((string) $header)), $headers);
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];

            if (count(array_unique($headers)) !== count($headers)) {
                throw ValidationException::withMessages([
                    'file' => 'CSV headers contain a duplicate column.',
                ]);
            }

            $missingHeaders = array_diff(self::REQUIRED_HEADERS, $headers);
            $unknownHeaders = array_diff($headers, [...self::REQUIRED_HEADERS, ...self::OPTIONAL_HEADERS]);

            if ($missingHeaders !== [] || $unknownHeaders !== []) {
                throw ValidationException::withMessages([
                    'file' => 'CSV headers are invalid. Required: '.implode(', ', self::REQUIRED_HEADERS)
                        .'. Optional: '.implode(', ', self::OPTIONAL_HEADERS).'.',
                ]);
            }

            $existingStaffNumbers = User::query()->whereNotNull('staff_number')->pluck('staff_number')
                ->map(fn (string $value): string => strtoupper($value))->all();
            $existingEmails = User::query()->whereNotNull('email')->pluck('email')
                ->map(fn (string $value): string => strtolower($value))->all();
            $activeDepartmentCodes = array_fill_keys(
                Department::query()->where('is_active', true)->pluck('code')->map(Str::upper(...))->all(),
                true,
            );
            $rows = [];
            $seenStaffNumbers = [];
            $seenEmails = [];
            $lineNumber = 1;

            while (($cells = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
                $lineNumber++;

                if (count(array_filter($cells, fn ($cell): bool => trim((string) $cell) !== '')) === 0) {
                    continue;
                }

                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['file' => 'CSV imports are limited to 500 data rows.']);
                }

                if (count($cells) !== count($headers)) {
                    $rows[] = [
                        'line_number' => $lineNumber,
                        'status' => 'invalid',
                        'data' => [],
                        'errors' => ['The row has a different number of columns than the header.'],
                    ];

                    continue;
                }

                $raw = array_combine($headers, array_map(fn ($value): string => trim((string) $value), $cells));
                $data = [
                    'staff_number' => strtoupper($raw['staff_number']),
                    'name' => $raw['name'],
                    'email' => $raw['email'] === '' ? null : strtolower($raw['email']),
                    'department_code' => strtoupper($raw['department_code']),
                    'designation' => $raw['designation'],
                    'employment_status' => strtolower($raw['employment_status']),
                    'phone_number' => $raw['phone_number'] === '' ? null : $raw['phone_number'],
                    'personal_email' => $raw['personal_email'] === '' ? null : strtolower($raw['personal_email']),
                    'residential_address' => $raw['residential_address'] === '' ? null : $raw['residential_address'],
                ];

                foreach (self::OPTIONAL_HEADERS as $header) {
                    $value = $raw[$header] ?? '';
                    $data[$header] = $value === '' ? null : match ($header) {
                        'gender', 'marital_status' => strtolower($value),
                        default => $value,
                    };
                }

                $validator = Validator::make($data, [
                    'staff_number' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9][A-Z0-9\/-]{0,31}$/'],
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['nullable', 'email', 'max:255'],
                    'department_code' => ['required', 'string', 'max:20', Rule::in(DepartmentCatalog::codes())],
                    'designation' => ['required', 'string', 'max:255'],
                    'employment_status' => ['required', Rule::in(['employed', 'on_leave', 'seconded', 'retired', 'terminated'])],
                    'phone_number' => ['nullable', 'string', 'max:40'],
                    'personal_email' => ['nullable', 'email', 'max:255'],
                    'residential_address' => ['nullable', 'string', 'max:1000'],
                    'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
                    'grade_level' => ['nullable', 'string', 'max:40'],
                    'unit' => ['nullable', 'string', 'max:120'],
                    'date_of_first_appointment' => ['nullable', 'date'],
                    'date_of_birth' => ['nullable', 'date', 'before:today'],
                    'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'other'])],
                    'next_of_kin' => ['nullable', 'string', 'max:255'],
                    'next_of_kin_phone' => ['nullable', 'string', 'max:40'],
                ]);
                $errors = $validator->errors()->all();

                if ($data['department_code'] !== '' && ! isset($activeDepartmentCodes[$data['department_code']])) {
                    $errors[] = 'Department code does not match an active department.';
                }

                if ($data['staff_number'] !== '' && in_array($data['staff_number'], $existingStaffNumbers, true)) {
                    $errors[] = 'Staff number already exists.';
                }
                if ($data['staff_number'] !== '' && isset($seenStaffNumbers[$data['staff_number']])) {
                    $errors[] = 'Duplicate staff number in this file.';
                }
                if ($data['email'] !== null && in_array($data['email'], $existingEmails, true)) {
                    $errors[] = 'Institutional email already exists.';
                }
                if ($data['email'] !== null && isset($seenEmails[$data['email']])) {
                    $errors[] = 'Duplicate institutional email in this file.';
                }

                if ($data['staff_number'] !== '') {
                    $seenStaffNumbers[$data['staff_number']] = true;
                }
                if ($data['email'] !== null) {
                    $seenEmails[$data['email']] = true;
                }

                $rows[] = [
                    'line_number' => $lineNumber,
                    'status' => $errors === [] ? 'valid' : 'invalid',
                    'data' => $data,
                    'errors' => $errors,
                ];
            }

            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'The CSV contains no data rows.']);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
