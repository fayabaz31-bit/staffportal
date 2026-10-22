<?php

namespace App\Http\Controllers;

use App\DepartmentCatalog;
use App\Models\Department;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonnelDirectoryController extends Controller
{
    public function create(): View
    {
        return view('registry.personnel.create', [
            'departments' => Department::query()
                ->whereIn('code', DepartmentCatalog::codes())
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'staff_number' => Str::upper(trim($request->string('staff_number')->toString())),
            'email' => $request->filled('email') ? Str::lower(trim($request->string('email')->toString())) : null,
        ]);

        $validated = $request->validate([
            'staff_number' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9][A-Z0-9\/-]{0,31}$/', Rule::unique('users', 'staff_number')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'department_id' => ['required', Rule::exists('departments', 'id')->whereIn('code', DepartmentCatalog::codes())->where('is_active', true)],
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

        try {
            DB::transaction(function () use ($validated): void {
                $department = Department::query()
                    ->whereKey($validated['department_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $user = User::create([
                    'staff_number' => $validated['staff_number'],
                    'name' => trim($validated['name']),
                    'email' => $validated['email'] ?? null,
                    'password' => Str::random(64),
                    'status' => 'suspended',
                    'role' => UserRole::Staff,
                ]);

                $user->staffProfile()->create([
                    'department_id' => $department->id,
                    'designation' => trim($validated['designation']),
                    'gender' => $validated['gender'] ?? null,
                    'grade_level' => $validated['grade_level'] ?? null,
                    'unit' => $validated['unit'] ?? null,
                    'date_of_first_appointment' => $validated['date_of_first_appointment'] ?? null,
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'marital_status' => $validated['marital_status'] ?? null,
                    'next_of_kin' => $validated['next_of_kin'] ?? null,
                    'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
                    'employment_status' => $validated['employment_status'],
                    'phone_number' => $validated['phone_number'] ?? null,
                    'personal_email' => $validated['personal_email'] ?? null,
                    'residential_address' => $validated['residential_address'] ?? null,
                ]);
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1062) {
                throw $exception;
            }

            return redirect()->route('registry.personnel.create')
                ->withErrors(['staff_number' => 'A staff account with this identifier or email already exists.'])
                ->withInput($request->except(['password', 'role']));
        }

        return redirect()->route('registry.personnel.index')
            ->with('status', 'Staff record created as a suspended account. ICT must provision access separately.');
    }

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $departmentCode = Str::upper(trim($request->string('department')->toString()));

        $departments = Department::query()
            ->whereIn('code', DepartmentCatalog::codes())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $staffProfiles = StaffProfile::query()
            ->with(['user', 'department'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('staff_number', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })->orWhereHas('department', function (Builder $departmentQuery) use ($search): void {
                        $departmentQuery->where('name', 'like', '%'.$search.'%');
                    });
                });
            })
            ->when($departmentCode !== '', function (Builder $query) use ($departmentCode): void {
                $query->whereHas('department', function (Builder $departmentQuery) use ($departmentCode): void {
                    $departmentQuery->where('code', $departmentCode);
                });
            })
            ->orderBy('department_id')
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        $orderedCodes = DepartmentCatalog::codes();
        $groupedProfiles = $staffProfiles->getCollection()
            ->groupBy(fn (StaffProfile $profile): string => $profile->department?->code ?? 'UNASSIGNED')
            ->sortBy(function ($rows, string $code) use ($orderedCodes): int {
                $position = array_search($code, $orderedCodes, true);

                return $position === false ? PHP_INT_MAX : $position;
            });

        $departmentCounts = StaffProfile::query()
            ->selectRaw('department_id, COUNT(*) as aggregate')
            ->groupBy('department_id')
            ->pluck('aggregate', 'department_id');

        return view('registry.personnel.index', [
            'staffProfiles' => $staffProfiles,
            'groupedProfiles' => $groupedProfiles,
            'departments' => $departments,
            'departmentCounts' => $departmentCounts,
            'departmentCode' => $departmentCode,
            'search' => $search,
        ]);
    }
}
