<?php

namespace Database\Seeders;

use App\DepartmentCatalog;
use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds a realistic 50-record staff directory across all seven official
 * departments. The ten demo role accounts are created by DatabaseSeeder; this
 * seeder adds the remaining forty staff members so the directory totals fifty.
 *
 * Records use updateOrCreate on the staff number, so re-seeding is safe.
 */
class StaffDirectorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Staff members added on top of the demo role accounts.
     *
     * @var list<array{name: string, gender: string, department: string, designation: string, unit: string, grade: string}>
     */
    private const array STAFF = [
        // Academics (12)
        ['name' => 'Oluwaseun Adebayo', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Senior French Language Instructor', 'unit' => 'Language Studies Unit', 'grade' => 'GL 13'],
        ['name' => 'Chiamaka Nwosu', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'French Studies Unit', 'grade' => 'GL 12'],
        ['name' => 'Ibrahim Danjuma', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer II', 'unit' => 'Translation and Interpretation Unit', 'grade' => 'GL 11'],
        ['name' => 'Folake Adeoye', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Assistant Lecturer', 'unit' => 'Language Laboratory Unit', 'grade' => 'GL 09'],
        ['name' => 'Kelechi Opara', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Curriculum Officer', 'unit' => 'Academic Planning Unit', 'grade' => 'GL 10'],
        ['name' => 'Zainab Lawal', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Examinations Officer', 'unit' => 'Examinations Unit', 'grade' => 'GL 10'],
        ['name' => 'Babatunde Ojo', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Senior Lecturer', 'unit' => 'French Studies Unit', 'grade' => 'GL 14'],
        ['name' => 'Ifeoma Okonkwo', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'Translation and Interpretation Unit', 'grade' => 'GL 12'],
        ['name' => 'Yakubu Musa', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Language Laboratory Technologist', 'unit' => 'Language Laboratory Unit', 'grade' => 'GL 08'],
        ['name' => 'Temitope Akinyemi', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer II', 'unit' => 'Language Studies Unit', 'grade' => 'GL 11'],
        ['name' => 'Nnamdi Okeke', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Assistant Lecturer', 'unit' => 'French Studies Unit', 'grade' => 'GL 09'],
        ['name' => 'Aisha Abdullahi', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Academic Records Officer', 'unit' => 'Academic Planning Unit', 'grade' => 'GL 08'],

        // Registry (4)
        ['name' => 'Gbenga Oladipo', 'gender' => 'male', 'department' => DepartmentCatalog::REGISTRY, 'designation' => 'Administrative Officer', 'unit' => 'Personnel Unit', 'grade' => 'GL 10'],
        ['name' => 'Adaobi Nwachukwu', 'gender' => 'female', 'department' => DepartmentCatalog::REGISTRY, 'designation' => 'Records Officer', 'unit' => 'Records and Correspondence Unit', 'grade' => 'GL 09'],
        ['name' => 'Suleiman Bello', 'gender' => 'male', 'department' => DepartmentCatalog::REGISTRY, 'designation' => 'Verification Officer', 'unit' => 'Personnel Unit', 'grade' => 'GL 08'],
        ['name' => 'Yewande Oyelaran', 'gender' => 'female', 'department' => DepartmentCatalog::REGISTRY, 'designation' => 'Confidential Secretary', 'unit' => 'Registry Office', 'grade' => 'GL 07'],

        // Bursary (6)
        ['name' => 'Chukwudi Obi', 'gender' => 'male', 'department' => DepartmentCatalog::BURSARY, 'designation' => 'Accountant', 'unit' => 'Accounts Unit', 'grade' => 'GL 12'],
        ['name' => 'Hauwa Mohammed', 'gender' => 'female', 'department' => DepartmentCatalog::BURSARY, 'designation' => 'Payroll Officer', 'unit' => 'Payroll Unit', 'grade' => 'GL 10'],
        ['name' => 'Kunle Adewale', 'gender' => 'male', 'department' => DepartmentCatalog::BURSARY, 'designation' => 'Bursary Officer', 'unit' => 'Revenue Unit', 'grade' => 'GL 09'],
        ['name' => 'Nneka Eze', 'gender' => 'female', 'department' => DepartmentCatalog::BURSARY, 'designation' => 'Internal Audit Officer', 'unit' => 'Internal Audit Unit', 'grade' => 'GL 12'],
        ['name' => 'Abubakar Sadiq', 'gender' => 'male', 'department' => DepartmentCatalog::BURSARY, 'designation' => 'Procurement Officer', 'unit' => 'Procurement Unit', 'grade' => 'GL 10'],
        ['name' => 'Bukola Ajayi', 'gender' => 'female', 'department' => DepartmentCatalog::BURSARY, 'designation' => 'Cashier', 'unit' => 'Cash Office', 'grade' => 'GL 08'],

        // Health and Services (6)
        ['name' => 'Emeka Chukwu', 'gender' => 'male', 'department' => DepartmentCatalog::HEALTH_AND_SERVICES, 'designation' => 'Medical Officer', 'unit' => 'Medical Services Unit', 'grade' => 'GL 16'],
        ['name' => 'Fatima Usman', 'gender' => 'female', 'department' => DepartmentCatalog::HEALTH_AND_SERVICES, 'designation' => 'Senior Nursing Officer', 'unit' => 'Nursing Unit', 'grade' => 'GL 14'],
        ['name' => 'Segun Ogunbiyi', 'gender' => 'male', 'department' => DepartmentCatalog::HEALTH_AND_SERVICES, 'designation' => 'Community Health Officer', 'unit' => 'Community Health Unit', 'grade' => 'GL 09'],
        ['name' => 'Ijeoma Madu', 'gender' => 'female', 'department' => DepartmentCatalog::HEALTH_AND_SERVICES, 'designation' => 'Medical Laboratory Scientist', 'unit' => 'Laboratory Unit', 'grade' => 'GL 12'],
        ['name' => 'Aliyu Garba', 'gender' => 'male', 'department' => DepartmentCatalog::HEALTH_AND_SERVICES, 'designation' => 'Health Records Officer', 'unit' => 'Health Records Unit', 'grade' => 'GL 08'],
        ['name' => 'Ronke Ojo', 'gender' => 'female', 'department' => DepartmentCatalog::HEALTH_AND_SERVICES, 'designation' => 'Nursing Officer', 'unit' => 'Nursing Unit', 'grade' => 'GL 10'],

        // Works and Services (5)
        ['name' => 'Obinna Uche', 'gender' => 'male', 'department' => DepartmentCatalog::WORKS_AND_SERVICES, 'designation' => 'Civil Engineer', 'unit' => 'Works Unit', 'grade' => 'GL 14'],
        ['name' => 'Salamatu Ibrahim', 'gender' => 'female', 'department' => DepartmentCatalog::WORKS_AND_SERVICES, 'designation' => 'Electrical Engineer', 'unit' => 'Electrical Unit', 'grade' => 'GL 13'],
        ['name' => 'Wale Ogunleye', 'gender' => 'male', 'department' => DepartmentCatalog::WORKS_AND_SERVICES, 'designation' => 'ICT Support Officer', 'unit' => 'ICT Unit', 'grade' => 'GL 09'],
        ['name' => 'Chinwe Onyeka', 'gender' => 'female', 'department' => DepartmentCatalog::WORKS_AND_SERVICES, 'designation' => 'Facility Officer', 'unit' => 'Works Unit', 'grade' => 'GL 10'],
        ['name' => 'Idris Mohammed', 'gender' => 'male', 'department' => DepartmentCatalog::WORKS_AND_SERVICES, 'designation' => 'Maintenance Officer', 'unit' => 'Maintenance Unit', 'grade' => 'GL 09'],

        // Staff School (5)
        ['name' => 'Bisi Olawale', 'gender' => 'female', 'department' => DepartmentCatalog::STAFF_SCHOOL, 'designation' => 'Head Teacher', 'unit' => 'Staff School', 'grade' => 'GL 15'],
        ['name' => 'Tochukwu Eze', 'gender' => 'male', 'department' => DepartmentCatalog::STAFF_SCHOOL, 'designation' => 'Classroom Teacher', 'unit' => 'Staff School', 'grade' => 'GL 09'],
        ['name' => 'Maryam Sani', 'gender' => 'female', 'department' => DepartmentCatalog::STAFF_SCHOOL, 'designation' => 'Early Years Teacher', 'unit' => 'Staff School', 'grade' => 'GL 08'],
        ['name' => 'Tayo Adekunle', 'gender' => 'male', 'department' => DepartmentCatalog::STAFF_SCHOOL, 'designation' => 'Classroom Teacher', 'unit' => 'Staff School', 'grade' => 'GL 09'],
        ['name' => 'Chisom Ibe', 'gender' => 'female', 'department' => DepartmentCatalog::STAFF_SCHOOL, 'designation' => 'School Administrator', 'unit' => 'Staff School', 'grade' => 'GL 10'],

        // Directorate (2)
        ['name' => 'Haruna Aliyu', 'gender' => 'male', 'department' => DepartmentCatalog::DIRECTORATE, 'designation' => 'Executive Assistant', 'unit' => 'Executive Office', 'grade' => 'GL 12'],
        ['name' => 'Omolara Adebisi', 'gender' => 'female', 'department' => DepartmentCatalog::DIRECTORATE, 'designation' => 'Special Adviser', 'unit' => 'Executive Office', 'grade' => 'GL 16'],
    ];

    /**
     * Nigerian first names used to build a plausible next-of-kin record.
     *
     * @var list<string>
     */
    private const array NEXT_OF_KIN_FIRST_NAMES = [
        'Funmilayo', 'Chika', 'Aisha', 'Bola', 'Nkechi', 'Sadiq', 'Yetunde',
        'Uche', 'Hauwa', 'Tunde', 'Amaka', 'Bello', 'Damilola', 'Ifeanyi',
        'Zainab', 'Femi', 'Chinonso', 'Maryam', 'Kunle', 'Adaeze',
    ];

    /**
     * @var list<string>
     */
    private const array RESIDENTIAL_ADDRESSES = [
        '12 Marina Road, Badagry, Lagos',
        '8 Market Street, Badagry, Lagos',
        '24 Lagos-Badagry Expressway, Badagry, Lagos',
        '5 Agbara Road, Badagry, Lagos',
        '17 Kweme Road, Badagry, Lagos',
        '3 Topo Road, Badagry, Lagos',
        '21 Seme Road, Badagry, Lagos',
        '9 Ibereko Road, Badagry, Lagos',
        '14 Ajara Road, Badagry, Lagos',
        '6 Aradagun Road, Badagry, Lagos',
    ];

    /**
     * @var list<string>
     */
    private const array MARITAL_STATUSES = [
        'married', 'single', 'married', 'married', 'single',
        'divorced', 'widowed', 'married',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DepartmentCatalog::sync();

        $departments = Department::query()->pluck('id', 'code');
        $sharedPassword = Hash::make('password');

        foreach (self::STAFF as $index => $staff) {
            $staffNumber = 'NFLV-'.str_pad((string) (11 + $index), 4, '0', STR_PAD_LEFT);
            $name = $staff['name'];
            $surname = Str::afterLast($name, ' ');
            $gradeNumber = (int) preg_replace('/\D+/', '', $staff['grade']);
            $nextOfKin = self::NEXT_OF_KIN_FIRST_NAMES[$index % count(self::NEXT_OF_KIN_FIRST_NAMES)].' '.$surname;

            $user = User::query()->updateOrCreate(['staff_number' => $staffNumber], [
                'name' => $name,
                'email' => Str::slug($name, '.').'@nfvl.test',
                'password' => $sharedPassword,
                'status' => 'active',
                'role' => UserRole::Staff,
            ]);

            $user->staffProfile()->updateOrCreate([], [
                'department_id' => $departments[$staff['department']],
                'designation' => $staff['designation'],
                'gender' => $staff['gender'],
                'grade_level' => $staff['grade'],
                'unit' => $staff['unit'],
                'date_of_first_appointment' => now()
                    ->subYears(max(1, $gradeNumber - 6) + ($index % 4))
                    ->subDays($index * 7)
                    ->toDateString(),
                'date_of_birth' => now()
                    ->subYears(26 + $gradeNumber)
                    ->subDays($index * 19)
                    ->toDateString(),
                'marital_status' => self::MARITAL_STATUSES[$index % count(self::MARITAL_STATUSES)],
                'next_of_kin' => $nextOfKin,
                'next_of_kin_phone' => sprintf('+234 806 %03d %04d', 100 + $index, 3000 + ($index * 11)),
                'employment_status' => 'employed',
                'phone_number' => sprintf('+234 803 %03d %04d', 100 + $index, 1000 + ($index * 13)),
                'personal_email' => Str::slug($name, '.').'@example.test',
                'residential_address' => self::RESIDENTIAL_ADDRESSES[$index % count(self::RESIDENTIAL_ADDRESSES)],
            ]);
        }
    }
}
