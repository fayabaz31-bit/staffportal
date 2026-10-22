<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPersonnelEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_can_open_the_manual_entry_form(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        Department::factory()->create(['code' => 'ICT', 'name' => 'Information Technology']);

        $response = $this->actingAs($hr)->get(route('registry.personnel.create'));

        $response->assertOk()
            ->assertSeeText('Add staff member')
            ->assertSeeText('Information Technology');
    }

    public function test_non_registry_user_cannot_open_or_submit_manual_entry(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $form = $this->actingAs($staff)->get(route('registry.personnel.create'));
        $submit = $this->actingAs($staff)->post(route('registry.personnel.store'), $this->validPayload());

        $form->assertForbidden();
        $submit->assertForbidden();
        $this->assertDatabaseMissing('users', ['staff_number' => 'NFLV-2200']);
    }

    public function test_registry_can_create_a_suspended_staff_account_with_optional_email(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $department = Department::factory()->create(['code' => 'ICT', 'name' => 'Information Technology']);

        $response = $this->actingAs($hr)->post(route('registry.personnel.store'), [
            ...$this->validPayload(),
            'email' => '',
            'role' => UserRole::IctSuperadmin->value,
            'status' => 'active',
        ]);

        $response->assertRedirectToRoute('registry.personnel.index')
            ->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'staff_number' => 'NFLV-2200',
            'name' => 'Ada Sample',
            'email' => null,
            'role' => UserRole::Staff->value,
            'status' => 'suspended',
        ]);
        $this->assertDatabaseHas('staff_profiles', [
            'department_id' => $department->id,
            'designation' => 'Analyst',
            'employment_status' => 'employed',
            'phone_number' => '+2348000002200',
        ]);
    }

    public function test_duplicate_staff_number_is_rejected(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        User::factory()->create(['staff_number' => 'NFLV-2200']);
        Department::factory()->create(['code' => 'ICT', 'name' => 'Information Technology']);

        $response = $this->actingAs($hr)->from(route('registry.personnel.create'))
            ->post(route('registry.personnel.store'), $this->validPayload());

        $response->assertRedirectToRoute('registry.personnel.create')
            ->assertSessionHasErrors('staff_number');
        $this->assertDatabaseCount('users', 2);
    }

    private function validPayload(): array
    {
        return [
            'staff_number' => 'NFLV-2200',
            'name' => 'Ada Sample',
            'email' => 'ada.sample@example.test',
            'department_id' => Department::query()->firstOrCreate(
                ['code' => 'ICT'],
                ['name' => 'Information Technology', 'is_active' => true],
            )->id,
            'designation' => 'Analyst',
            'employment_status' => 'employed',
            'phone_number' => '+2348000002200',
            'personal_email' => 'ada.personal@example.test',
            'residential_address' => 'Badagry',
        ];
    }
}
