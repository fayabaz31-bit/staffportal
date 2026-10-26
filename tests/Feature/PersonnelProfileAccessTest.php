<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\Department;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelProfileAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_leaders_can_open_full_staff_profiles_from_the_directory(): void
    {
        $targetProfile = $this->targetProfile();

        $leaders = [
            'Head of HR' => $this->leader(UserRole::HrRegistryAdmin, DutyCatalog::REGISTRY_HEAD_REVIEW),
            'Registrar' => $this->leader(UserRole::HrRegistryAdmin, DutyCatalog::REGISTRY_REGISTRAR_APPROVE),
            'Director' => $this->leader(UserRole::Director, DutyCatalog::REGISTRY_DIRECTOR_ESCALATION),
        ];

        foreach ($leaders as $title => $leader) {
            $directory = $this->actingAs($leader)->get(route('registry.personnel.index', [
                'search' => 'NFLV-7777',
            ]));

            $directory->assertOk()
                ->assertSeeText('Nneka Full Record')
                ->assertSee(route('registry.personnel.show', $targetProfile), false)
                ->assertSeeText('View profile');

            if ($title === 'Director') {
                $directory->assertDontSeeText('Add staff member')
                    ->assertDontSeeText('Import personnel');

                $this->actingAs($leader)
                    ->get(route('registry.personnel.create'))
                    ->assertForbidden();
                $this->actingAs($leader)
                    ->get(route('registry.personnel-imports.create'))
                    ->assertForbidden();
            }

            $this->actingAs($leader)
                ->get(route('registry.personnel.show', $targetProfile))
                ->assertOk()
                ->assertSeeText('Nneka Full Record')
                ->assertSeeText('NFLV-7777')
                ->assertSeeText('nneka.record@nfvl.test')
                ->assertSeeText('Registry')
                ->assertSeeText('Records Officer')
                ->assertSeeText('Records Unit')
                ->assertSeeText('GL 09')
                ->assertSeeText('On Leave')
                ->assertSeeText('12 Apr 1990')
                ->assertSeeText('1 Sep 2016')
                ->assertSeeText('Female')
                ->assertSeeText('Married')
                ->assertSeeText('+234 803 111 7777')
                ->assertSeeText('nneka.personal@example.test')
                ->assertSeeText('Ada Nneka')
                ->assertSeeText('+234 810 000 7777')
                ->assertSeeText('12 Marina Road, Badagry, Lagos')
                ->assertSeeText('Suspended');
        }
    }

    public function test_technical_and_institutional_administrators_do_not_gain_personnel_profile_access(): void
    {
        $targetProfile = $this->targetProfile();

        $unauthorisedUsers = [
            User::factory()->create(['role' => UserRole::IctSuperadmin]),
            User::factory()->create(['role' => UserRole::InstitutionalSuperadmin]),
            User::factory()->create(['role' => UserRole::Staff]),
        ];

        foreach ($unauthorisedUsers as $user) {
            $this->actingAs($user)
                ->get(route('registry.personnel.index'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('registry.personnel.show', $targetProfile))
                ->assertForbidden();
        }
    }

    public function test_opening_a_full_staff_profile_is_recorded_in_the_audit_trail(): void
    {
        $headOfHr = $this->leader(UserRole::HrRegistryAdmin, DutyCatalog::REGISTRY_HEAD_REVIEW);
        $targetProfile = $this->targetProfile();

        $this->actingAs($headOfHr)
            ->get(route('registry.personnel.show', $targetProfile))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $headOfHr->id,
            'action' => 'personnel.profile_viewed',
            'subject_type' => $targetProfile->getMorphClass(),
            'subject_id' => $targetProfile->id,
        ]);
    }

    private function targetProfile(): StaffProfile
    {
        $department = Department::factory()->create([
            'code' => 'REGISTRY',
            'name' => 'Registry',
        ]);

        $user = User::factory()->create([
            'name' => 'Nneka Full Record',
            'email' => 'nneka.record@nfvl.test',
            'staff_number' => 'NFLV-7777',
            'role' => UserRole::Staff,
            'status' => 'suspended',
        ]);

        return StaffProfile::factory()
            ->for($user)
            ->for($department)
            ->create([
                'designation' => 'Records Officer',
                'unit' => 'Records Unit',
                'grade_level' => 'GL 09',
                'employment_status' => 'on_leave',
                'gender' => 'female',
                'date_of_birth' => '1990-04-12',
                'date_of_first_appointment' => '2016-09-01',
                'marital_status' => 'married',
                'next_of_kin' => 'Ada Nneka',
                'next_of_kin_phone' => '+234 810 000 7777',
                'phone_number' => '+234 803 111 7777',
                'personal_email' => 'nneka.personal@example.test',
                'residential_address' => '12 Marina Road, Badagry, Lagos',
            ]);
    }

    private function leader(UserRole $role, string $dutyCode): User
    {
        $user = User::factory()->create(['role' => $role]);
        $duty = DutyCatalog::ensureDuty($dutyCode);

        $user->duties()->attach($duty->getKey(), ['assigned_at' => now()]);
        $user->unsetRelation('duties');

        return $user->fresh();
    }
}
