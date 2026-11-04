<?php

namespace Database\Seeders;

use App\DepartmentCatalog;
use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Adds synthetic Registry records to make the local staff directory realistic.
 */
class RegistryStaffDirectorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DepartmentCatalog::sync();

        $registry = Department::query()
            ->where('code', DepartmentCatalog::REGISTRY)
            ->firstOrFail();

        $sharedPassword = Hash::make('password');

        foreach ($this->staff() as $index => $staff) {
            $staffNumber = 'NFLV-'.str_pad((string) (101 + $index), 4, '0', STR_PAD_LEFT);

            $user = User::query()->updateOrCreate(['staff_number' => $staffNumber], [
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => $sharedPassword,
                'status' => 'active',
                'role' => UserRole::Staff,
            ]);

            $user->staffProfile()->updateOrCreate([], [
                'department_id' => $registry->id,
                'designation' => $staff['designation'],
                'gender' => $staff['gender'],
                'grade_level' => $staff['gradeLevel'],
                'unit' => $staff['unit'],
                'employment_status' => 'employed',
                'phone_number' => sprintf('+234 803 70%d %04d', $index % 10, 1400 + $index),
                'personal_email' => $staff['email'],
                'residential_address' => 'Badagry, Lagos State',
            ]);
        }
    }

    /**
     * @return list<array{name: string, email: string, gender: string, designation: string, gradeLevel: string, unit: string}>
     */
    private function staff(): array
    {
        return [
            ['name' => 'Abiola Adebayo', 'email' => 'abiola.adebayo@nfvl.test', 'gender' => 'female', 'designation' => 'Administrative Officer I', 'gradeLevel' => 'GL 10', 'unit' => 'Personnel Unit'],
            ['name' => 'Chinedu Okafor', 'email' => 'chinedu.okafor@nfvl.test', 'gender' => 'male', 'designation' => 'Administrative Officer II', 'gradeLevel' => 'GL 09', 'unit' => 'Records and Correspondence Unit'],
            ['name' => 'Bolanle Ogunleye', 'email' => 'bolanle.ogunleye@nfvl.test', 'gender' => 'female', 'designation' => 'Senior Administrative Officer', 'gradeLevel' => 'GL 12', 'unit' => 'Registry Office'],
            ['name' => 'Ibrahim Sani', 'email' => 'ibrahim.sani@nfvl.test', 'gender' => 'male', 'designation' => 'Higher Executive Officer', 'gradeLevel' => 'GL 08', 'unit' => 'Admissions and Registration Unit'],
            ['name' => 'Ngozi Eze', 'email' => 'ngozi.eze@nfvl.test', 'gender' => 'female', 'designation' => 'Administrative Assistant', 'gradeLevel' => 'GL 07', 'unit' => 'Personnel Unit'],
            ['name' => 'Adekunle Yusuf', 'email' => 'adekunle.yusuf@nfvl.test', 'gender' => 'male', 'designation' => 'Principal Administrative Officer', 'gradeLevel' => 'GL 14', 'unit' => 'Registry Office'],
            ['name' => 'Rukayat Bello', 'email' => 'rukayat.bello@nfvl.test', 'gender' => 'female', 'designation' => 'Executive Officer', 'gradeLevel' => 'GL 07', 'unit' => 'Records and Correspondence Unit'],
            ['name' => 'Emeka Nwankwo', 'email' => 'emeka.nwankwo@nfvl.test', 'gender' => 'male', 'designation' => 'Administrative Officer I', 'gradeLevel' => 'GL 10', 'unit' => 'Admissions and Registration Unit'],
            ['name' => 'Funmilayo Ajayi', 'email' => 'funmilayo.ajayi@nfvl.test', 'gender' => 'female', 'designation' => 'Senior Executive Officer', 'gradeLevel' => 'GL 09', 'unit' => 'Personnel Unit'],
            ['name' => 'Tunde Adeyemi', 'email' => 'tunde.adeyemi@nfvl.test', 'gender' => 'male', 'designation' => 'Administrative Officer II', 'gradeLevel' => 'GL 09', 'unit' => 'Records and Correspondence Unit'],
            ['name' => 'Aisha Mohammed', 'email' => 'aisha.mohammed@nfvl.test', 'gender' => 'female', 'designation' => 'Higher Executive Officer', 'gradeLevel' => 'GL 08', 'unit' => 'Admissions and Registration Unit'],
            ['name' => 'Oluwaseun Olaniyi', 'email' => 'oluwaseun.olaniyi@nfvl.test', 'gender' => 'male', 'designation' => 'Administrative Officer I', 'gradeLevel' => 'GL 10', 'unit' => 'Registry Office'],
            ['name' => 'Adaobi Uche', 'email' => 'adaobi.uche@nfvl.test', 'gender' => 'female', 'designation' => 'Executive Officer', 'gradeLevel' => 'GL 07', 'unit' => 'Personnel Unit'],
            ['name' => 'Musa Abdullahi', 'email' => 'musa.abdullahi@nfvl.test', 'gender' => 'male', 'designation' => 'Senior Administrative Officer', 'gradeLevel' => 'GL 12', 'unit' => 'Records and Correspondence Unit'],
            ['name' => 'Kemi Akinola', 'email' => 'kemi.akinola.registry@nfvl.test', 'gender' => 'female', 'designation' => 'Administrative Officer II', 'gradeLevel' => 'GL 09', 'unit' => 'Admissions and Registration Unit'],
            ['name' => 'Nnamdi Obi', 'email' => 'nnamdi.obi@nfvl.test', 'gender' => 'male', 'designation' => 'Executive Officer', 'gradeLevel' => 'GL 07', 'unit' => 'Registry Office'],
            ['name' => 'Hadiza Garba', 'email' => 'hadiza.garba@nfvl.test', 'gender' => 'female', 'designation' => 'Principal Administrative Officer', 'gradeLevel' => 'GL 14', 'unit' => 'Personnel Unit'],
            ['name' => 'Opeyemi Fashola', 'email' => 'opeyemi.fashola@nfvl.test', 'gender' => 'male', 'designation' => 'Higher Executive Officer', 'gradeLevel' => 'GL 08', 'unit' => 'Records and Correspondence Unit'],
            ['name' => 'Chisom Onuoha', 'email' => 'chisom.onuoha@nfvl.test', 'gender' => 'female', 'designation' => 'Administrative Officer I', 'gradeLevel' => 'GL 10', 'unit' => 'Admissions and Registration Unit'],
            ['name' => 'Babajide Oladipo', 'email' => 'babajide.oladipo@nfvl.test', 'gender' => 'male', 'designation' => 'Senior Executive Officer', 'gradeLevel' => 'GL 09', 'unit' => 'Registry Office'],
        ];
    }
}
