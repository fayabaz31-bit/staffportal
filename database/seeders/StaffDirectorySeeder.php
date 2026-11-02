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
 * Seeds staff directory. Populates Academics with 50 staff members as
 * specified, using only allowed designations and units for Academics.
 */
class StaffDirectorySeeder extends Seeder
{
    use WithoutModelEvents;

    private const array ACADEMICS_DESIGNATIONS = [
        'Assistant lecturer',
        'Graduate assistant',
        'Lecturer I',
        'Lecturer II',
        'Senior lecturer',
        'Associate professor',
        'Professor',
    ];

    private const array ACADEMICS_UNITS = [
        'Linguistics',
        'Translation and interpretation',
        'Literary & cultural studies',
        'Language & communication',
    ];

    private const array NIGERIAN_FIRST_NAMES_MALE = [
        'Oluwaseun', 'Ibrahim', 'Babatunde', 'Yakubu', 'Nnamdi', 'Chukwudi', 'Abubakar',
        'Segun', 'Emeka', 'Obinna', 'Idris', 'Kunle', 'Tayo', 'Tochukwu', 'Gbenga',
        'Suleiman', 'Adebola', 'Olumide', 'Ademola', 'Adewale', 'Chinedu', 'Musa',
        'Uche', 'Femi', 'Olatunji', 'Tunde', 'Bello', 'Jide', 'Kola', 'Lanre',
        'Opeyemi', 'Adekunle', 'Oluwafemi', 'Ayodeji', 'Babajide', 'Olusegun', 'Taofeek',
        'Abiodun', 'Oladipo', 'Akinwale',
    ];

    private const array NIGERIAN_FIRST_NAMES_FEMALE = [
        'Chiamaka', 'Folake', 'Zainab', 'Ifeoma', 'Aisha', 'Temitope', 'Adaobi',
        'Yewande', 'Hauwa', 'Nneka', 'Bukola', 'Ijeoma', 'Ronke', 'Fatima',
        'Bisi', 'Maryam', 'Chisom', 'Omolara', 'Funmilayo', 'Yetunde', 'Amaka',
        'Nkechi', 'Adaeze', 'Damilola', 'Kemi', 'Bola', 'Shade', 'Tolu', 'Rukayat',
        'Hadiza', 'Oluwatoyin', 'Ayobami', 'Modupe', 'Omowunmi', 'Adetutu', 'Titilayo',
        'Tolulope', 'Olayinka', 'Morenike', 'Ibironke',
    ];

    private const array NIGERIAN_LAST_NAMES = [
        'Adebayo', 'Nwosu', 'Danjuma', 'Adeoye', 'Opara', 'Lawal', 'Ojo', 'Okonkwo',
        'Musa', 'Akinyemi', 'Okeke', 'Abdullahi', 'Oladipo', 'Nwachukwu', 'Bello',
        'Oyelaran', 'Obi', 'Mohammed', 'Adewale', 'Eze', 'Sadiq', 'Ajayi', 'Chukwu',
        'Usman', 'Ogunbiyi', 'Madu', 'Garba', 'Uche', 'Ibrahim', 'Ogunleye', 'Onyeka',
        'Olawale', 'Adekunle', 'Ibe', 'Aliyu', 'Adebisi', 'Okafor', 'Balogun', 'Yusuf',
        'Adesanya', 'Ogunlade', 'Onuoha', 'Abubakar', 'Sule', 'Oladimeji', 'Akande',
        'Ezeani', 'Nwankwo', 'Akinola', 'Ogunbanjo', 'Olaniyi', 'Adeyemi', 'Fashola',
        'Oseni', 'Bamidele', 'Odukoya', 'Anyanwu', 'Ifeanyi', 'Udo', 'Ekpo',
    ];

    private const array NIGERIAN_STATES = [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue',
        'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'Gombe',
        'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara',
        'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau',
        'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara', 'FCT',
    ];

    private const array PFAS = [
        'Premium Pension Limited',
        'ARM Pension Managers Limited',
        'Stanbic IBTC Pension Managers Limited',
        'Leadway Pensure PFA Limited',
        'NLPC Pension Fund Administrators Limited',
        'Trustfund Pensions Limited',
        'CrusaderSterling Pensions Limited',
        'Access Pensions Limited',
        'Fidelity Pension Managers Limited',
        'Guaranty Trust Pension Managers Limited',
    ];

    /**
     * @var list<array{name: string, gender: string, department: string, designation: string, unit: string, grade: string}>
     */
    private array $staff = [];

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

        $this->buildAcademicsStaff();

        $baseStaff = [
            // Academics samples
            ['name' => 'Oluwaseun Adebayo', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Senior lecturer', 'unit' => 'Linguistics', 'grade' => 'GL 14'],
            ['name' => 'Chiamaka Nwosu', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'Literary & cultural studies', 'grade' => 'GL 12'],
            ['name' => 'Ibrahim Danjuma', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer II', 'unit' => 'Translation and interpretation', 'grade' => 'GL 11'],
            ['name' => 'Folake Adeoye', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Assistant lecturer', 'unit' => 'Language & communication', 'grade' => 'GL 09'],
            ['name' => 'Zainab Lawal', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'Linguistics', 'grade' => 'GL 12'],
            ['name' => 'Babatunde Ojo', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Associate professor', 'unit' => 'Literary & cultural studies', 'grade' => 'GL 15'],
            ['name' => 'Ifeoma Okonkwo', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'Translation and interpretation', 'grade' => 'GL 12'],
            ['name' => 'Temitope Akinyemi', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer II', 'unit' => 'Language & communication', 'grade' => 'GL 11'],
            ['name' => 'Nnamdi Okeke', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Assistant lecturer', 'unit' => 'Linguistics', 'grade' => 'GL 09'],
            ['name' => 'Aisha Abdullahi', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Graduate assistant', 'unit' => 'Literary & cultural studies', 'grade' => 'GL 07'],
            ['name' => 'Emeka Chukwu', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Professor', 'unit' => 'Linguistics', 'grade' => 'GL 17'],
            ['name' => 'Fatima Usman', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Associate professor', 'unit' => 'Translation and interpretation', 'grade' => 'GL 15'],
        ];

        $allStaff = array_merge($baseStaff, $this->staff);

        foreach ($allStaff as $index => $staff) {
            $staffNumber = 'NFLV-'.str_pad((string) (11 + $index), 4, '0', STR_PAD_LEFT);
            $name = $staff['name'];
            $surname = Str::afterLast($name, ' ');
            $gradeNumber = (int) preg_replace('/\D+/', '', $staff['grade']);
            $gender = $staff['gender'];
            $stateOfOrigin = self::NIGERIAN_STATES[$index % count(self::NIGERIAN_STATES)];
            $pfa = self::PFAS[$index % count(self::PFAS)];
            $pencomNumber = 'PEN'.str_pad((string) (1000000000 + $index), 10, '0', STR_PAD_LEFT);
            $nextOfKinFirst = $gender === 'male'
                ? self::NIGERIAN_FIRST_NAMES_FEMALE[$index % count(self::NIGERIAN_FIRST_NAMES_FEMALE)]
                : self::NIGERIAN_FIRST_NAMES_MALE[$index % count(self::NIGERIAN_FIRST_NAMES_MALE)];
            $nextOfKin = $nextOfKinFirst.' '.$surname;

            $educationalQualifications = match ($staff['designation']) {
                'Professor' => "Ph.D. in {$staff['unit']}, University of Ibadan\nM.A. in {$staff['unit']}, University of Lagos\nB.A. in {$staff['unit']}, Ahmadu Bello University",
                'Associate professor' => "Ph.D. in {$staff['unit']}, Obafemi Awolowo University\nM.A. in {$staff['unit']}, University of Nigeria, Nsukka\nB.A. in {$staff['unit']}, University of Benin",
                'Senior lecturer' => "Ph.D. in {$staff['unit']}, University of Ilorin\nM.A. in {$staff['unit']}, Bayero University Kano\nB.A. in {$staff['unit']}, Nnamdi Azikiwe University",
                'Lecturer I' => "M.A. in {$staff['unit']}, University of Lagos\nB.A. in {$staff['unit']}, University of Ibadan",
                'Lecturer II' => "M.A. in {$staff['unit']}, Obafemi Awolowo University\nB.A. in {$staff['unit']}, University of Nigeria, Nsukka",
                'Assistant lecturer' => "B.A. in {$staff['unit']}, University of Benin\nPostgraduate Diploma in Education, National Teachers' Institute",
                'Graduate assistant' => "B.A. in {$staff['unit']}, Ahmadu Bello University",
                default => 'Relevant academic qualifications as appropriate.',
            };

            $professionalQualifications = match ($staff['designation']) {
                'Professor', 'Associate professor' => "Member, Nigerian Academy of Letters\nFellow, Society for Translation Studies in Nigeria",
                'Senior lecturer', 'Lecturer I' => "Member, Nigerian Linguistic Association\nMember, African Studies Association of Africa",
                'Lecturer II', 'Assistant lecturer' => 'Member, Nigerian Linguistic Association',
                'Graduate assistant' => 'Member, Young Scholars Forum',
                default => null,
            };

            $baseEmail = Str::slug($name, '.').'@nfvl.test';
            $email = $baseEmail;
            $emailSuffix = 1;
            while (User::query()->where('email', $email)->where('staff_number', '!=', $staffNumber)->exists()) {
                $email = str_replace('@nfvl.test', '+'.$emailSuffix.'@nfvl.test', $baseEmail);
                $emailSuffix++;
            }

            $user = User::query()->updateOrCreate(['staff_number' => $staffNumber], [
                'name' => $name,
                'email' => $email,
                'password' => $sharedPassword,
                'status' => 'active',
                'role' => UserRole::Staff,
            ]);

            $user->staffProfile()->updateOrCreate([], [
                'department_id' => $departments[$staff['department']],
                'designation' => $staff['designation'],
                'gender' => $gender,
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
                'next_of_kin_phone' => sprintf('+234 806 %03d %04d', 100 + ($index % 900), 3000 + ($index * 11) % 7000),
                'employment_status' => 'employed',
                'phone_number' => sprintf('+234 803 %03d %04d', 100 + ($index % 900), 1000 + ($index * 13) % 9000),
                'personal_email' => Str::slug($name, '.').'@example.test',
                'residential_address' => self::RESIDENTIAL_ADDRESSES[$index % count(self::RESIDENTIAL_ADDRESSES)],
                'state_of_origin' => $stateOfOrigin,
                'educational_qualifications' => $educationalQualifications,
                'professional_qualifications' => $professionalQualifications,
                'pencom_number' => $pencomNumber,
                'pfa_name' => $pfa,
            ]);
        }
    }

    private function buildAcademicsStaff(): void
    {
        $this->staff = [];
        $usedNames = [];

        $grades = [
            'Graduate assistant' => ['GL 07'],
            'Assistant lecturer' => ['GL 08', 'GL 09'],
            'Lecturer II' => ['GL 10', 'GL 11'],
            'Lecturer I' => ['GL 12'],
            'Senior lecturer' => ['GL 13', 'GL 14'],
            'Associate professor' => ['GL 15'],
            'Professor' => ['GL 16', 'GL 17'],
        ];

        $baseStaff = [
            ['name' => 'Oluwaseun Adebayo', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Senior lecturer', 'unit' => 'Linguistics', 'grade' => 'GL 14'],
            ['name' => 'Chiamaka Nwosu', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'Literary & cultural studies', 'grade' => 'GL 12'],
            ['name' => 'Ibrahim Danjuma', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer II', 'unit' => 'Translation and interpretation', 'grade' => 'GL 11'],
            ['name' => 'Folake Adeoye', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Assistant lecturer', 'unit' => 'Language & communication', 'grade' => 'GL 09'],
            ['name' => 'Zainab Lawal', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'Linguistics', 'grade' => 'GL 12'],
            ['name' => 'Babatunde Ojo', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Associate professor', 'unit' => 'Literary & cultural studies', 'grade' => 'GL 15'],
            ['name' => 'Ifeoma Okonkwo', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer I', 'unit' => 'Translation and interpretation', 'grade' => 'GL 12'],
            ['name' => 'Temitope Akinyemi', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Lecturer II', 'unit' => 'Language & communication', 'grade' => 'GL 11'],
            ['name' => 'Nnamdi Okeke', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Assistant lecturer', 'unit' => 'Linguistics', 'grade' => 'GL 09'],
            ['name' => 'Aisha Abdullahi', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Graduate assistant', 'unit' => 'Literary & cultural studies', 'grade' => 'GL 07'],
            ['name' => 'Emeka Chukwu', 'gender' => 'male', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Professor', 'unit' => 'Linguistics', 'grade' => 'GL 17'],
            ['name' => 'Fatima Usman', 'gender' => 'female', 'department' => DepartmentCatalog::ACADEMICS, 'designation' => 'Associate professor', 'unit' => 'Translation and interpretation', 'grade' => 'GL 15'],
        ];

        $count = 50 - count($baseStaff);
        $index = 0;

        while (count($this->staff) < $count && $index < $count * 3) {
            $gender = $index % 3 === 0 ? 'female' : 'male';
            $firstNames = $gender === 'male' ? self::NIGERIAN_FIRST_NAMES_MALE : self::NIGERIAN_FIRST_NAMES_FEMALE;
            $firstName = $firstNames[$index % count($firstNames)];
            $lastName = self::NIGERIAN_LAST_NAMES[($index * 7) % count(self::NIGERIAN_LAST_NAMES)];
            $name = $firstName.' '.$lastName;

            if (in_array($name, $usedNames, true)) {
                $index++;

                continue;
            }
            $usedNames[] = $name;

            $designationIndex = $index % count(self::ACADEMICS_DESIGNATIONS);
            $designation = self::ACADEMICS_DESIGNATIONS[$designationIndex];
            $unit = self::ACADEMICS_UNITS[$index % count(self::ACADEMICS_UNITS)];
            $gradeOptions = $grades[$designation];
            $grade = $gradeOptions[$index % count($gradeOptions)];

            $this->staff[] = [
                'name' => $name,
                'gender' => $gender,
                'department' => DepartmentCatalog::ACADEMICS,
                'designation' => $designation,
                'unit' => $unit,
                'grade' => $grade,
            ];

            $index++;
        }
    }
}
