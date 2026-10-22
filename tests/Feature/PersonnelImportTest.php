<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\PersonnelImportBatch;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PersonnelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_directory_lists_personnel_records(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $staff = User::factory()->create([
            'name' => 'Nneka Sample',
            'staff_number' => 'NFLV-1200',
        ]);
        $department = Department::factory()->create(['name' => 'Languages']);
        StaffProfile::factory()->for($staff)->for($department)->create([
            'designation' => 'Instructor',
        ]);

        $response = $this->actingAs($hr)->get(route('registry.personnel.index', ['search' => 'NFLV-1200']));

        $response->assertOk()
            ->assertSeeText('Nneka Sample')
            ->assertSeeText('NFLV-1200')
            ->assertSeeText('Languages')
            ->assertSeeText('Instructor');
    }

    public function test_staff_cannot_access_registry_directory_or_import(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $directory = $this->actingAs($staff)->get(route('registry.personnel.index'));
        $preview = $this->actingAs($staff)->post(route('registry.personnel-imports.store'), [
            'file' => $this->csvUpload($this->validCsv()),
        ]);

        $directory->assertForbidden();
        $preview->assertForbidden();
    }

    public function test_registry_user_cannot_view_another_uploaders_preview(): void
    {
        $uploader = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $otherHr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $batch = PersonnelImportBatch::create([
            'uploaded_by' => $uploader->id,
            'original_filename' => 'personnel.csv',
            'status' => PersonnelImportBatch::STATUS_PREVIEW,
            'row_count' => 1,
            'valid_row_count' => 1,
            'error_row_count' => 0,
            'preview_rows' => [],
        ]);

        $response = $this->actingAs($otherHr)->get(route('registry.personnel-imports.show', $batch));

        $response->assertNotFound();
    }

    public function test_valid_csv_preview_does_not_create_staff_accounts(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        Department::factory()->create(['code' => 'ICT', 'name' => 'Information Technology']);

        $response = $this->actingAs($hr)->post(route('registry.personnel-imports.store'), [
            'file' => $this->csvUpload($this->validCsv()),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['staff_number' => 'NFLV-1200']);
        $batch = PersonnelImportBatch::query()->latest('id')->firstOrFail();

        $preview = $this->get(route('registry.personnel-imports.show', $batch));

        $preview->assertOk()
            ->assertSeeText('PREVIEW ONLY')
            ->assertSeeText('Nneka Sample')
            ->assertSeeText('Ready to import');
    }

    public function test_duplicate_staff_numbers_block_the_preview_commit(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        Department::factory()->create(['code' => 'ICT', 'name' => 'Information Technology']);
        $duplicateCsv = $this->validCsv()."NFLV-1200,Second Sample,,ICT,Analyst,employed,,,\n";

        $response = $this->actingAs($hr)->post(route('registry.personnel-imports.store'), [
            'file' => $this->csvUpload($duplicateCsv),
        ]);

        $response->assertRedirect();
        $batch = PersonnelImportBatch::query()->latest('id')->firstOrFail();
        $preview = $this->get(route('registry.personnel-imports.show', $batch));

        $preview->assertOk()
            ->assertSeeText('Duplicate staff number in this file')
            ->assertDontSeeText('Commit 1 staff records');
        $this->assertDatabaseMissing('users', ['staff_number' => 'NFLV-1200']);

        $this->post(route('registry.personnel-imports.commit', $batch))
            ->assertRedirectToRoute('registry.personnel-imports.show', $batch)
            ->assertSessionHasErrors('import');
    }

    public function test_commit_creates_a_suspended_staff_account_and_profile_without_email(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        Department::factory()->create(['code' => 'ICT', 'name' => 'Information Technology']);
        $csv = "staff_number,name,email,department_code,designation,employment_status,phone_number,personal_email,residential_address\n"
            .'NFLV-1200,Nneka Sample,,ICT,Instructor,employed,+2348000001200,,Badagry'."\n";

        $previewResponse = $this->actingAs($hr)->post(route('registry.personnel-imports.store'), [
            'file' => $this->csvUpload($csv),
        ]);
        $previewResponse->assertRedirect();
        $batch = PersonnelImportBatch::query()->latest('id')->firstOrFail();

        $response = $this->post(route('registry.personnel-imports.commit', $batch));

        $response->assertRedirectToRoute('registry.personnel.index')
            ->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'staff_number' => 'NFLV-1200',
            'name' => 'Nneka Sample',
            'email' => null,
            'role' => UserRole::Staff->value,
            'status' => 'suspended',
        ]);
        $this->assertDatabaseHas('staff_profiles', [
            'designation' => 'Instructor',
            'employment_status' => 'employed',
            'phone_number' => '+2348000001200',
        ]);
    }

    public function test_extended_csv_import_persists_personnel_detail_fields(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        Department::factory()->create(['code' => 'REGISTRY', 'name' => 'Registry']);

        $headers = 'staff_number,name,email,department_code,designation,employment_status,phone_number,personal_email,residential_address,gender,grade_level,unit,date_of_first_appointment,date_of_birth,marital_status,next_of_kin,next_of_kin_phone';
        $row = 'NFLV-1300,Ada Example,ada@example.test,REGISTRY,Personnel Officer,employed,,,,'.
            'female,GL 08,Personnel Unit,2019-09-02,1990-04-11,married,John Example,+2348000001300';

        $this->actingAs($hr)->post(route('registry.personnel-imports.store'), [
            'file' => $this->csvUpload($headers."\n".$row."\n"),
        ])->assertRedirect();

        $batch = PersonnelImportBatch::query()->latest('id')->firstOrFail();
        $this->assertSame(1, $batch->valid_row_count);

        $this->post(route('registry.personnel-imports.commit', $batch))
            ->assertRedirectToRoute('registry.personnel.index');

        $profile = StaffProfile::query()->whereHas('user', fn ($query) => $query->where('staff_number', 'NFLV-1300'))->sole();

        $this->assertSame('female', $profile->gender);
        $this->assertSame('GL 08', $profile->grade_level);
        $this->assertSame('Personnel Unit', $profile->unit);
        $this->assertSame('2019-09-02', $profile->date_of_first_appointment?->toDateString());
        $this->assertSame('1990-04-11', $profile->date_of_birth?->toDateString());
        $this->assertSame('married', $profile->marital_status);
        $this->assertSame('John Example', $profile->next_of_kin);
        $this->assertSame('+2348000001300', $profile->next_of_kin_phone);
    }

    public function test_import_preview_groups_rows_by_official_department_order(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        Department::factory()->create(['code' => 'ACADEMICS', 'name' => 'Academics']);
        Department::factory()->create(['code' => 'BURSARY', 'name' => 'Bursary']);

        $csv = "staff_number,name,email,department_code,designation,employment_status,phone_number,personal_email,residential_address\n"
            ."NFLV-1401,Bursary Person,,BURSARY,Accounts Clerk,employed,,,\n"
            ."NFLV-1402,Academic Person,,ACADEMICS,Lecturer,employed,,,\n";

        $this->actingAs($hr)->post(route('registry.personnel-imports.store'), [
            'file' => $this->csvUpload($csv),
        ])->assertRedirect();

        $batch = PersonnelImportBatch::query()->latest('id')->firstOrFail();

        $this->get(route('registry.personnel-imports.show', $batch))
            ->assertOk()
            ->assertSeeInOrder(['Academics', 'Academic Person', 'Bursary', 'Bursary Person']);
    }

    public function test_directory_can_be_filtered_to_one_official_department(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $academics = Department::factory()->create(['code' => 'ACADEMICS', 'name' => 'Academics']);
        $bursary = Department::factory()->create(['code' => 'BURSARY', 'name' => 'Bursary']);

        $lecturer = User::factory()->create(['name' => 'Academic Person', 'staff_number' => 'NFLV-1501']);
        StaffProfile::factory()->for($lecturer)->for($academics)->create();

        $clerk = User::factory()->create(['name' => 'Bursary Person', 'staff_number' => 'NFLV-1502']);
        StaffProfile::factory()->for($clerk)->for($bursary)->create();

        $this->actingAs($hr)
            ->get(route('registry.personnel.index', ['department' => 'BURSARY']))
            ->assertOk()
            ->assertSeeText('Bursary Person')
            ->assertSeeText('Bursary')
            ->assertDontSeeText('Academic Person');
    }

    private function csvUpload(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('personnel.csv', $contents);
    }

    private function validCsv(): string
    {
        return "staff_number,name,email,department_code,designation,employment_status,phone_number,personal_email,residential_address\n"
            .'NFLV-1200,Nneka Sample,nneka@example.test,ICT,Instructor,employed,+2348000001200,nneka.personal@example.test,Badagry'."\n";
    }
}
