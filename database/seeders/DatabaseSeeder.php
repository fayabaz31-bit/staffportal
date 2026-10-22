<?php

namespace Database\Seeders;

use App\DepartmentCatalog;
use App\DutyCatalog;
use App\Models\Department;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\OrganisationalUnit;
use App\Models\ReportingRelationship;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\RegistryWorkflowCatalog;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->syncDepartments();

        $departments = Department::query()->pluck('id', 'code');

        foreach (UserRole::cases() as $role) {
            $user = User::updateOrCreate(['staff_number' => $role->demoStaffNumber()], [
                'name' => $role->demoName(),
                'email' => $role->demoEmail(),
                'password' => 'password',
                'status' => 'active',
                'role' => $role,
            ]);

            [$departmentCode, $designation] = match ($role) {
                UserRole::Staff => [DepartmentCatalog::ACADEMICS, 'Language Instructor'],
                UserRole::HrRegistryAdmin => [DepartmentCatalog::REGISTRY, 'Head of Human Resources'],
                UserRole::BursaryAdmin => [DepartmentCatalog::BURSARY, 'Bursary Officer'],
                UserRole::HeadOfDepartment => [DepartmentCatalog::ACADEMICS, 'Department Head'],
                UserRole::Director => [DepartmentCatalog::DIRECTORATE, 'Director / Chief Executive'],
                UserRole::IctSuperadmin => [DepartmentCatalog::WORKS_AND_SERVICES, 'ICT Administrator'],
                UserRole::InstitutionalSuperadmin => [DepartmentCatalog::REGISTRY, 'Institutional Administrator'],
                UserRole::AuditorReadonly => [DepartmentCatalog::REGISTRY, 'Read-only Auditor'],
            };

            $user->staffProfile()->updateOrCreate([], [
                'department_id' => $departments[$departmentCode],
                'designation' => $designation,
                'employment_status' => 'employed',
                'phone_number' => '+234 800 000 '.substr($role->demoStaffNumber(), -4),
                'personal_email' => 'contact.'.substr($role->demoStaffNumber(), -4).'@example.test',
                'residential_address' => 'Demo Residence, Badagry',
            ]);
        }

        $this->seedRegistryPilotAccounts($departments);

        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'ANNUAL',
                'description' => 'Standard statutory annual recreation leave.',
                'default_days_per_year' => 30,
                'requires_attachment' => false,
                'requires_supervisor_review' => true,
                'requires_hr_verification' => true,
            ],
            [
                'name' => 'Casual Leave',
                'code' => 'CASUAL',
                'description' => 'Short compassionate absence for urgent personal affairs.',
                'default_days_per_year' => 7,
                'requires_attachment' => false,
                'requires_supervisor_review' => true,
                'requires_hr_verification' => false,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SICK',
                'description' => 'Medical absence supported by a physician report.',
                'default_days_per_year' => 14,
                'requires_attachment' => true,
                'requires_supervisor_review' => true,
                'requires_hr_verification' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'MATERNITY',
                'description' => 'Statutory maternal leave supported by medical documentation.',
                'default_days_per_year' => 84,
                'requires_attachment' => true,
                'requires_supervisor_review' => true,
                'requires_hr_verification' => true,
            ],
        ];

        foreach ($leaveTypes as $data) {
            $lt = LeaveType::updateOrCreate(['code' => $data['code']], $data);

            // Seed demo entitlement for demo staff
            foreach (User::all() as $u) {
                LeaveEntitlement::updateOrCreate(
                    ['user_id' => $u->id, 'leave_type_id' => $lt->id, 'year' => 2026],
                    [
                        'allocated_days' => $data['default_days_per_year'],
                        'used_days' => 0,
                        'pending_days' => 0,
                        'is_policy_configured' => true, // Synthetic test/demo policy enabled
                    ]
                );
            }
        }

        $this->call(DutySeeder::class);

        $this->seedRegistryHierarchy();
        RegistryWorkflowCatalog::sync();
    }

    /**
     * Create the canonical departments and remap legacy demo records.
     */
    private function syncDepartments(): void
    {
        DepartmentCatalog::sync();

        $legacy = [
            'LANG' => DepartmentCatalog::ACADEMICS,
            'ICT' => DepartmentCatalog::WORKS_AND_SERVICES,
            'EXEC' => DepartmentCatalog::DIRECTORATE,
            'REG' => DepartmentCatalog::REGISTRY,
            'BUR' => DepartmentCatalog::BURSARY,
        ];

        foreach ($legacy as $oldCode => $newCode) {
            $old = Department::query()->where('code', $oldCode)->first();
            $new = Department::query()->where('code', $newCode)->first();

            if ($old === null || $new === null) {
                continue;
            }

            StaffProfile::query()
                ->where('department_id', $old->id)
                ->update(['department_id' => $new->id]);

            $old->delete();
        }
    }

    /**
     * Demo accounts for the Registry pilot: Registrar and Personnel Officer.
     *
     * These use the HR/Registry role so they receive the Registry dashboard, but
     * their duties are narrowed to what their office actually authorises.
     *
     * @param  Collection<string, int>  $departments
     */
    private function seedRegistryPilotAccounts($departments): void
    {
        $registrar = User::updateOrCreate(['staff_number' => 'NFLV-0009'], [
            'name' => 'Demo Registrar',
            'email' => 'demo.registrar@nfvl.test',
            'password' => 'password',
            'status' => 'active',
            'role' => UserRole::HrRegistryAdmin,
        ]);

        $personnelOfficer = User::updateOrCreate(['staff_number' => 'NFLV-0010'], [
            'name' => 'Demo Personnel Officer',
            'email' => 'demo.personnel.officer@nfvl.test',
            'password' => 'password',
            'status' => 'active',
            'role' => UserRole::HrRegistryAdmin,
        ]);

        foreach ([$registrar, $personnelOfficer] as $officer) {
            $officer->staffProfile()->updateOrCreate([], [
                'department_id' => $departments[DepartmentCatalog::REGISTRY],
                'designation' => $officer->is($registrar) ? 'Registrar' : 'Personnel Officer',
                'employment_status' => 'employed',
                'phone_number' => '+234 800 000 '.substr($officer->staff_number, -4),
                'personal_email' => 'contact.'.substr($officer->staff_number, -4).'@example.test',
                'residential_address' => 'Demo Residence, Badagry',
            ]);
        }
    }

    /**
     * Seed the Registry chain-of-command duties and reporting lines.
     */
    private function seedRegistryHierarchy(): void
    {
        $headOfHr = User::query()->where('staff_number', 'NFLV-0002')->first();
        $registrar = User::query()->where('staff_number', 'NFLV-0009')->first();
        $personnelOfficer = User::query()->where('staff_number', 'NFLV-0010')->first();
        $director = User::query()->where('staff_number', 'NFLV-0005')->first();

        $grants = [
            [$headOfHr, [DutyCatalog::REGISTRY_HEAD_REVIEW]],
            [$registrar, [DutyCatalog::REGISTRY_REGISTRAR_APPROVE]],
            [$director, [DutyCatalog::REGISTRY_DIRECTOR_ESCALATION]],
        ];

        foreach ($grants as [$user, $codes]) {
            if (! $user instanceof User) {
                continue;
            }

            $ids = collect($codes)->map(fn (string $code): int => DutyCatalog::ensureDuty($code)->getKey())->all();

            $user->duties()->syncWithoutDetaching(
                collect($ids)->mapWithKeys(fn (int $id): array => [$id => [
                    'assigned_by' => $director?->id,
                    'assigned_at' => now(),
                ]])->all(),
            );
        }

        $registry = Department::query()->where('code', DepartmentCatalog::REGISTRY)->first();
        $directorate = Department::query()->where('code', DepartmentCatalog::DIRECTORATE)->first();

        if ($registry === null) {
            return;
        }

        $personnelUnit = OrganisationalUnit::query()->updateOrCreate(
            ['code' => 'REG-PERSONNEL'],
            [
                'department_id' => $registry->id,
                'name' => 'Personnel Unit',
                'head_user_id' => $headOfHr?->id,
                'sort_order' => 10,
                'is_active' => true,
            ],
        );

        $recordsUnit = OrganisationalUnit::query()->updateOrCreate(
            ['code' => 'REG-RECORDS'],
            [
                'department_id' => $registry->id,
                'name' => 'Records and Correspondence Unit',
                'head_user_id' => $registrar?->id,
                'sort_order' => 20,
                'is_active' => true,
            ],
        );

        $lines = [
            [$personnelOfficer, $headOfHr, $personnelUnit],
            [$headOfHr, $registrar, $recordsUnit],
            [$registrar, $director, null],
        ];

        foreach ($lines as [$subordinate, $supervisor, $unit]) {
            if (! $subordinate instanceof User || ! $supervisor instanceof User) {
                continue;
            }

            ReportingRelationship::query()->updateOrCreate(
                [
                    'subordinate_id' => $subordinate->id,
                    'supervisor_id' => $supervisor->id,
                    'relationship_type' => ReportingRelationship::LINE_MANAGER,
                ],
                [
                    'organisational_unit_id' => $unit?->id,
                    'effective_from' => now()->startOfYear()->toDateString(),
                    'effective_to' => null,
                    'is_primary' => true,
                    'created_by' => $director?->id,
                ],
            );
        }

        if ($directorate !== null && $director !== null) {
            OrganisationalUnit::query()->updateOrCreate(
                ['code' => 'DIR-EXECUTIVE'],
                [
                    'department_id' => $directorate->id,
                    'name' => 'Executive Office',
                    'head_user_id' => $director->id,
                    'sort_order' => 10,
                    'is_active' => true,
                ],
            );
        }
    }
}
