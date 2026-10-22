<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\Department;
use App\Models\ProfileChangeRequest;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_only_the_authenticated_users_profile(): void
    {
        $user = User::factory()->create(['name' => 'Amina Staff']);
        $otherUser = User::factory()->create(['name' => 'Other Staff']);
        $department = $this->createDepartment('ICT', 'Information Technology');
        $this->createProfile($user, $department, 'Systems Officer');
        $this->createProfile($otherUser, $department, 'Network Officer');

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertOk()
            ->assertSeeText('Amina Staff')
            ->assertSeeText('Information Technology')
            ->assertSeeText('Systems Officer')
            ->assertDontSeeText('Other Staff');
    }

    public function test_guest_is_redirected_before_viewing_profile(): void
    {
        $response = $this->get(route('profile.show'));

        $response->assertRedirectToRoute('login');
    }

    public function test_staff_can_update_contact_fields_without_changing_official_fields(): void
    {
        $user = User::factory()->create(['name' => 'Amina Staff']);
        $profile = $this->createProfile($user);

        $response = $this->actingAs($user)->patch(route('profile.contact.update'), [
            'phone_number' => '+234 801 234 5678',
            'personal_email' => 'amina.personal@example.test',
            'residential_address' => '10 Lagoon Road, Badagry',
            'name' => 'Unapproved Name',
            'employment_status' => 'retired',
        ]);

        $response->assertRedirectToRoute('profile.show');
        $this->assertDatabaseHas('staff_profiles', [
            'id' => $profile->id,
            'phone_number' => '+234 801 234 5678',
            'personal_email' => 'amina.personal@example.test',
            'residential_address' => '10 Lagoon Road, Badagry',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Amina Staff',
        ]);
        $this->assertDatabaseHas('staff_profiles', [
            'id' => $profile->id,
            'employment_status' => 'employed',
        ]);
    }

    public function test_official_field_change_is_recorded_as_pending_without_changing_profile(): void
    {
        $user = User::factory()->create(['name' => 'Amina Staff']);
        $this->createProfile($user);

        $response = $this->actingAs($user)->post(route('profile.change-requests.store'), [
            'field_key' => 'name',
            'proposed_value' => 'Amina Adeyemi',
            'reason' => 'My legal name has changed.',
        ]);

        $response->assertRedirectToRoute('profile.show');
        $this->assertDatabaseHas('profile_change_requests', [
            'user_id' => $user->id,
            'field_key' => 'name',
            'current_value' => 'Amina Staff',
            'proposed_value' => 'Amina Adeyemi',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Amina Staff',
        ]);
    }

    public function test_user_cannot_submit_a_change_for_an_unapproved_field(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);

        $response = $this->actingAs($user)->from(route('profile.show'))->post(route('profile.change-requests.store'), [
            'field_key' => 'salary',
            'proposed_value' => '900000',
            'reason' => 'Please change this.',
        ]);

        $response->assertRedirectToRoute('profile.show')
            ->assertSessionHasErrors('field_key');
        $this->assertDatabaseCount('profile_change_requests', 0);
    }

    public function test_only_hr_registry_can_view_the_review_queue(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $response = $this->actingAs($staff)->get(route('registry.profile-change-requests.index'));

        $response->assertForbidden();
    }

    public function test_hr_registry_approval_applies_the_requested_official_change(): void
    {
        $staff = User::factory()->create(['name' => 'Amina Staff']);
        $this->createProfile($staff);
        $request = ProfileChangeRequest::create([
            'user_id' => $staff->id,
            'requested_by' => $staff->id,
            'field_key' => 'name',
            'current_value' => 'Amina Staff',
            'proposed_value' => 'Amina Adeyemi',
            'reason' => 'Legal name correction.',
            'status' => 'pending',
        ]);
        $headOfHr = $this->registryHeadOfHr();
        $registrar = $this->registryRegistrar();

        // The Head of HR reviews and forwards; the official record is untouched.
        $this->actingAs($headOfHr)
            ->post(route('registry.profile-change-requests.head-review', $request), [
                'head_note' => 'Supporting documents are in order.',
            ])
            ->assertRedirectToRoute('registry.profile-change-requests.index');

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Amina Staff']);
        $this->assertDatabaseHas('profile_change_requests', [
            'id' => $request->id,
            'status' => 'pending',
            'current_stage' => ProfileChangeRequest::STAGE_REGISTRAR,
            'head_reviewed_by' => $headOfHr->id,
        ]);

        // The Registrar gives final Registry approval; now the record changes.
        $response = $this->actingAs($registrar)->post(route('registry.profile-change-requests.registrar', $request), [
            'reviewer_note' => 'Verified against submitted records.',
        ]);

        $response->assertRedirectToRoute('registry.profile-change-requests.index');
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Amina Adeyemi',
        ]);
        $this->assertDatabaseHas('profile_change_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'current_stage' => ProfileChangeRequest::STAGE_COMPLETED,
            'reviewed_by' => $registrar->id,
            'registrar_approved_by' => $registrar->id,
        ]);
    }

    public function test_hr_registry_approval_updates_the_department_relationship(): void
    {
        $staff = User::factory()->create();
        $currentDepartment = $this->createDepartment('REG', 'Registry');
        $newDepartment = $this->createDepartment('ICT', 'Information Technology');
        $this->createProfile($staff, $currentDepartment, 'Clerk');
        $request = ProfileChangeRequest::create([
            'user_id' => $staff->id,
            'requested_by' => $staff->id,
            'field_key' => 'department',
            'current_value' => 'Registry',
            'proposed_value' => 'Information Technology',
            'reason' => 'Transfer approved by management.',
            'status' => 'pending',
        ]);

        $this->pushThroughRegistryReview($request);
        $registrar = $this->registryRegistrar();

        $response = $this->actingAs($registrar)->post(route('registry.profile-change-requests.registrar', $request));

        $response->assertRedirectToRoute('registry.profile-change-requests.index');
        $this->assertDatabaseHas('staff_profiles', [
            'user_id' => $staff->id,
            'department_id' => $newDepartment->id,
        ]);
    }

    public function test_hr_registry_rejection_keeps_the_canonical_record_unchanged(): void
    {
        $staff = User::factory()->create(['name' => 'Amina Staff']);
        $this->createProfile($staff);
        $request = ProfileChangeRequest::create([
            'user_id' => $staff->id,
            'requested_by' => $staff->id,
            'field_key' => 'name',
            'current_value' => 'Amina Staff',
            'proposed_value' => 'Amina Adeyemi',
            'reason' => 'Correction.',
            'status' => 'pending',
        ]);
        $headOfHr = $this->registryHeadOfHr();

        $response = $this->actingAs($headOfHr)->post(route('registry.profile-change-requests.reject', $request), [
            'reviewer_note' => 'Supporting documentation is required.',
        ]);

        $response->assertRedirectToRoute('registry.profile-change-requests.index');
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Amina Staff',
        ]);
        $this->assertDatabaseHas('profile_change_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reviewer_note' => 'Supporting documentation is required.',
        ]);
    }

    public function test_stale_profile_change_is_closed_without_overwriting_the_new_record(): void
    {
        $staff = User::factory()->create(['name' => 'Amina Staff']);
        $this->createProfile($staff);
        $request = ProfileChangeRequest::create([
            'user_id' => $staff->id,
            'requested_by' => $staff->id,
            'field_key' => 'name',
            'current_value' => 'Amina Staff',
            'proposed_value' => 'Amina Adeyemi',
            'reason' => 'Correction.',
            'status' => 'pending',
        ]);

        $this->pushThroughRegistryReview($request);
        $staff->update(['name' => 'Amina Updated']);
        $registrar = $this->registryRegistrar();

        $response = $this->actingAs($registrar)->post(route('registry.profile-change-requests.registrar', $request));

        $response->assertRedirectToRoute('registry.profile-change-requests.index')
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Amina Updated',
        ]);
        $this->assertDatabaseHas('profile_change_requests', [
            'id' => $request->id,
            'status' => 'rejected',
        ]);
    }

    public function test_staff_cannot_approve_an_official_change_request(): void
    {
        $staff = User::factory()->create();
        $request = ProfileChangeRequest::create([
            'user_id' => $staff->id,
            'requested_by' => $staff->id,
            'field_key' => 'name',
            'current_value' => $staff->name,
            'proposed_value' => 'Different Name',
            'reason' => 'Correction.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($staff)->post(route('registry.profile-change-requests.registrar', $request));

        $response->assertForbidden();
        $this->assertDatabaseHas('profile_change_requests', [
            'id' => $request->id,
            'status' => 'pending',
        ]);
    }

    /**
     * A Registry officer acting as Head of HR (first-line review, OD-20).
     */
    private function registryHeadOfHr(): User
    {
        $user = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $this->grantDuty($user, DutyCatalog::REGISTRY_HEAD_REVIEW);

        return $user->fresh();
    }

    /**
     * The Registrar: head of the Registry department, final Registry approval.
     */
    private function registryRegistrar(): User
    {
        $user = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $department = Department::query()->firstOrCreate(
            ['code' => 'REG'],
            ['name' => 'Registry', 'is_active' => true],
        );
        $this->createProfile($user, $department, 'Registrar');
        $this->grantDuty($user, DutyCatalog::REGISTRY_REGISTRAR_APPROVE);
        $this->grantDuty($user, DutyCatalog::REGISTRY_DIRECTOR_ESCALATION);

        return $user->fresh();
    }

    /**
     * Advance a request through the Head of HR stage so the Registrar can act.
     */
    private function pushThroughRegistryReview(ProfileChangeRequest $request): void
    {
        $this->actingAs($this->registryHeadOfHr())
            ->post(route('registry.profile-change-requests.head-review', $request))
            ->assertRedirectToRoute('registry.profile-change-requests.index');

        $request->refresh();
    }

    private function grantDuty(User $user, string $code): void
    {
        $duty = DutyCatalog::ensureDuty($code);

        if (! $user->duties()->whereKey($duty->getKey())->exists()) {
            $user->duties()->attach($duty->getKey(), ['assigned_at' => now()]);
        }

        $user->unsetRelation('duties');
    }

    private function createDepartment(string $code, string $name): Department
    {
        return Department::create([
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createProfile(User $user, ?Department $department = null, ?string $designation = null): StaffProfile
    {
        return StaffProfile::create([
            'user_id' => $user->id,
            'department_id' => $department?->id,
            'designation' => $designation,
            'employment_status' => 'employed',
        ]);
    }
}
