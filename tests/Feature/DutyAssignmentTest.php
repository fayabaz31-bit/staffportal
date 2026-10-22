<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\Department;
use App\Models\PayslipBatch;
use App\Models\ServiceRequest;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Database\Seeders\DutySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the duty ("desk") model that lets officers within the same department
 * hold genuinely different responsibilities: role baselines, per-officer grants
 * and withdrawal, service-desk routing, and payslip separation of duties.
 */
class DutyAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_baseline_duties_are_granted_when_an_account_is_created(): void
    {
        $registry = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $this->assertTrue($registry->hasDuty(
            DutyCatalog::REGISTRY_PERSONNEL_RECORDS,
            DutyCatalog::REGISTRY_SERVICE_DESK,
        ));

        $bursary = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $this->assertTrue($bursary->hasDuty(DutyCatalog::BURSARY_PAYSLIP_UPLOAD));
        // Separation of duties (OD-19): the baseline never combines upload and release.
        $this->assertFalse($bursary->hasDuty(DutyCatalog::BURSARY_PAYSLIP_RELEASE));

        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $this->assertSame([], $staff->dutyCodes());
    }

    public function test_changing_a_role_resets_duties_to_the_new_baseline(): void
    {
        $user = User::factory()->create(['role' => UserRole::Staff]);
        $this->assertSame([], $user->dutyCodes());

        $user->role = UserRole::BursaryAdmin;
        $user->save();

        $this->assertTrue($user->fresh()->hasDuty(DutyCatalog::BURSARY_PAYSLIP_UPLOAD));

        $user->role = UserRole::Staff;
        $user->save();

        $this->assertSame([], $user->fresh()->dutyCodes());
    }

    public function test_two_registry_officers_can_hold_different_duties(): void
    {
        $personnelOfficer = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $deskOfficer = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);

        $this->revokeDuty($personnelOfficer, DutyCatalog::REGISTRY_SERVICE_DESK);
        $this->revokeDuty($deskOfficer, DutyCatalog::REGISTRY_PERSONNEL_RECORDS);

        $this->assertTrue($personnelOfficer->hasDuty(DutyCatalog::REGISTRY_PERSONNEL_RECORDS));
        $this->assertFalse($personnelOfficer->hasDuty(DutyCatalog::REGISTRY_SERVICE_DESK));
        $this->assertTrue($deskOfficer->hasDuty(DutyCatalog::REGISTRY_SERVICE_DESK));
        $this->assertFalse($deskOfficer->hasDuty(DutyCatalog::REGISTRY_PERSONNEL_RECORDS));

        // The personnel-only officer cannot open the registry service desk queue.
        $this->actingAs($personnelOfficer)
            ->get(route('unit.service-requests.index', 'registry'))
            ->assertForbidden();

        // The desk officer can.
        $this->actingAs($deskOfficer)
            ->get(route('unit.service-requests.index', 'registry'))
            ->assertOk();
    }

    public function test_ict_can_grant_and_revoke_duties_with_audit_and_notification(): void
    {
        $ict = User::factory()->create(['role' => UserRole::IctSuperadmin]);
        $bursary = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        $this->revokeDuty($bursary, DutyCatalog::BURSARY_RECONCILIATION);
        $this->assertFalse($bursary->fresh()->hasDuty(DutyCatalog::BURSARY_RECONCILIATION));

        $this->actingAs($ict)
            ->post(route('ict.accounts.duties.grant', $bursary), ['duty' => DutyCatalog::BURSARY_RECONCILIATION])
            ->assertRedirect();

        $this->assertTrue($bursary->fresh()->hasDuty(DutyCatalog::BURSARY_RECONCILIATION));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.duty_granted',
            'subject_id' => $bursary->id,
            'actor_id' => $ict->id,
        ]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $bursary->id]);

        $this->actingAs($ict)
            ->post(route('ict.accounts.duties.revoke', $bursary), ['duty' => DutyCatalog::BURSARY_RECONCILIATION])
            ->assertRedirect();

        $this->assertFalse($bursary->fresh()->hasDuty(DutyCatalog::BURSARY_RECONCILIATION));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.duty_revoked',
            'subject_id' => $bursary->id,
        ]);
    }

    public function test_non_account_managers_cannot_assign_duties(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $target = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($hr)
            ->post(route('ict.accounts.duties.grant', $target), ['duty' => DutyCatalog::REGISTRY_SERVICE_DESK])
            ->assertForbidden();

        $this->assertSame([], $target->fresh()->dutyCodes());
    }

    public function test_payslip_release_requires_the_release_duty(): void
    {
        $uploader = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        $batch = PayslipBatch::factory()->create([
            'uploaded_by' => $uploader->id,
            'status' => PayslipBatch::STATUS_STAGED,
            'error_count' => 0,
            'reconciliation_summary' => ['matched' => []],
        ]);

        // The uploader keeps batch operations but cannot release.
        $this->actingAs($uploader)->get(route('bursary.payslips.index'))->assertOk();
        $this->actingAs($uploader)->post(route('bursary.payslips.publish', $batch))->assertForbidden();

        // A release approver (a different officer) may release.
        $approver = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $this->grantDuty($approver, DutyCatalog::BURSARY_PAYSLIP_RELEASE);
        $this->actingAs($approver)->post(route('bursary.payslips.publish', $batch))->assertRedirect();
    }

    public function test_separation_of_duties_blocks_the_uploader_from_releasing_their_own_batch(): void
    {
        $uploader = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        // Even if the uploader is also granted the release duty, they may not
        // release a batch they uploaded themselves.
        $this->grantDuty($uploader, DutyCatalog::BURSARY_PAYSLIP_RELEASE);
        $this->assertTrue($uploader->fresh()->hasDuty(DutyCatalog::BURSARY_PAYSLIP_RELEASE));

        $batch = PayslipBatch::factory()->create([
            'uploaded_by' => $uploader->id,
            'status' => PayslipBatch::STATUS_STAGED,
            'error_count' => 0,
            'reconciliation_summary' => ['matched' => []],
        ]);

        $this->actingAs($uploader)
            ->post(route('bursary.payslips.publish', $batch))
            ->assertSessionHas('error');

        $this->assertSame(PayslipBatch::STATUS_STAGED, $batch->fresh()->status);
    }

    public function test_assigning_the_release_duty_to_an_uploader_is_refused(): void
    {
        $ict = User::factory()->create(['role' => UserRole::IctSuperadmin]);
        $uploader = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $this->assertTrue($uploader->hasDuty(DutyCatalog::BURSARY_PAYSLIP_UPLOAD));

        $this->actingAs($ict)
            ->post(route('ict.accounts.duties.grant', $uploader), ['duty' => DutyCatalog::BURSARY_PAYSLIP_RELEASE])
            ->assertSessionHas('error');

        $this->assertFalse($uploader->fresh()->hasDuty(DutyCatalog::BURSARY_PAYSLIP_RELEASE));
    }

    public function test_a_unit_lead_can_assign_duties_within_their_own_unit(): void
    {
        $lead = $this->registryHeadOfHr();
        $colleague = User::factory()->create(['role' => UserRole::Staff]);
        $this->sameDepartment($lead, $colleague);

        // The lead withdraws a baseline duty from a registry colleague then adds it back.
        $this->actingAs($lead)
            ->get(route('ict.accounts.duties.index', $colleague))
            ->assertOk();

        $this->actingAs($lead)
            ->post(route('ict.accounts.duties.grant', $colleague), ['duty' => DutyCatalog::REGISTRY_SERVICE_DESK])
            ->assertRedirect();

        $this->assertTrue($colleague->fresh()->hasDuty(DutyCatalog::REGISTRY_SERVICE_DESK));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.duty_granted',
            'subject_id' => $colleague->id,
            'actor_id' => $lead->id,
        ]);
    }

    public function test_a_unit_lead_cannot_assign_duties_outside_their_unit(): void
    {
        $lead = $this->registryHeadOfHr();

        $bursaryOfficer = User::factory()->create(['role' => UserRole::BursaryAdmin]);
        $this->sameDepartment($lead, $bursaryOfficer);

        $this->actingAs($lead)
            ->post(route('ict.accounts.duties.grant', $bursaryOfficer), ['duty' => DutyCatalog::BURSARY_RECONCILIATION])
            ->assertForbidden();
    }

    public function test_a_unit_lead_cannot_grant_administrative_duties_or_edit_own_account(): void
    {
        $lead = $this->registryHeadOfHr();
        $colleague = User::factory()->create(['role' => UserRole::Staff]);
        $this->sameDepartment($lead, $colleague);

        // Administrative duties are ICT/Superadmin-only.
        $this->actingAs($lead)
            ->post(route('ict.accounts.duties.grant', $colleague), ['duty' => DutyCatalog::ACCOUNT_MANAGE])
            ->assertSessionHas('error');

        $this->assertFalse($colleague->fresh()->hasDuty(DutyCatalog::ACCOUNT_MANAGE));

        // A lead may not manage their own account.
        $this->actingAs($lead)
            ->get(route('ict.accounts.duties.index', $lead))
            ->assertForbidden();
    }

    public function test_service_request_handlers_are_resolved_by_duty(): void
    {
        $desk = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $personnel = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $this->revokeDuty($personnel, DutyCatalog::REGISTRY_SERVICE_DESK);

        $handlerIds = ServiceRequest::unitHandlerUsers('registry')->pluck('id');

        $this->assertTrue($handlerIds->contains($desk->id));
        $this->assertFalse($handlerIds->contains($personnel->id));
    }

    public function test_duty_catalogue_is_available_on_the_account_console(): void
    {
        $ict = User::factory()->create(['role' => UserRole::IctSuperadmin]);
        $target = User::factory()->create(['role' => UserRole::Staff]);
        $this->seed(DutySeeder::class);

        $this->actingAs($ict)
            ->get(route('ict.accounts.duties.index', $target))
            ->assertOk()
            ->assertSee('Bursary: payslip release')
            ->assertSee('Registry: service desk');
    }

    public function test_no_role_baseline_combines_the_upload_and_release_duties(): void
    {
        foreach (UserRole::cases() as $role) {
            $baseline = DutyCatalog::baselineFor($role);

            $holdsBoth = in_array(DutyCatalog::BURSARY_PAYSLIP_UPLOAD, $baseline, true)
                && in_array(DutyCatalog::BURSARY_PAYSLIP_RELEASE, $baseline, true);

            $this->assertFalse(
                $holdsBoth,
                "Role {$role->value} baseline combines payslip upload and release duties.",
            );
        }
    }

    public function test_each_unit_administrative_role_carries_duty_leadership_baseline(): void
    {
        // OD-20: HR/Registry leadership comes from explicit Registry office
        // duties, not from the generic `duty.lead` code, so a technical
        // administrator can never become the Registry's approving authority by
        // accident and a baseline account is not automatically the approver.
        $this->assertContains(
            DutyCatalog::REGISTRY_HEAD_REVIEW,
            DutyCatalog::DELEGATED_LEAD_DUTIES,
        );
        $this->assertNotContains(
            DutyCatalog::DUTY_LEAD,
            DutyCatalog::baselineFor(UserRole::HrRegistryAdmin),
        );
        $this->assertNotContains(
            DutyCatalog::REGISTRY_HEAD_REVIEW,
            DutyCatalog::baselineFor(UserRole::HrRegistryAdmin),
        );

        foreach ([
            UserRole::BursaryAdmin,
            UserRole::IctSuperadmin,
            UserRole::HeadOfDepartment,
            UserRole::InstitutionalSuperadmin,
        ] as $role) {
            $this->assertContains(
                DutyCatalog::DUTY_LEAD,
                DutyCatalog::baselineFor($role),
                "Role {$role->value} should lead duties within its unit.",
            );
        }
    }

    public function test_an_uploader_baseline_never_includes_the_release_duty_on_creation(): void
    {
        $bursary = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        $this->assertTrue($bursary->hasDuty(DutyCatalog::BURSARY_PAYSLIP_UPLOAD));
        $this->assertFalse($bursary->hasDuty(DutyCatalog::BURSARY_PAYSLIP_RELEASE));
        $this->assertSame([], $bursary->separationOfDutyConflicts());
    }

    public function test_a_head_of_department_reaches_the_team_duties_page(): void
    {
        $hod = User::factory()->create(['role' => UserRole::HeadOfDepartment]);
        $colleague = User::factory()->create(['role' => UserRole::Staff]);
        $this->sameDepartment($hod, $colleague);

        $this->actingAs($hod)
            ->get(route('ict.team-duties.index'))
            ->assertOk()
            ->assertSee('Assign duties to your team')
            ->assertSee($colleague->name);
    }

    public function test_a_generic_department_lead_manages_colleagues_by_department(): void
    {
        $hod = User::factory()->create(['role' => UserRole::HeadOfDepartment]);
        $colleague = User::factory()->create(['role' => UserRole::Staff]);
        $this->sameDepartment($hod, $colleague);

        $this->actingAs($hod)
            ->post(route('ict.accounts.duties.grant', $colleague), ['duty' => DutyCatalog::REGISTRY_SERVICE_DESK])
            ->assertRedirect();

        $this->assertTrue($colleague->fresh()->hasDuty(DutyCatalog::REGISTRY_SERVICE_DESK));
    }

    public function test_a_department_lead_cannot_manage_another_department(): void
    {
        $hod = User::factory()->create(['role' => UserRole::HeadOfDepartment]);
        $outsider = User::factory()->create(['role' => UserRole::Staff]);

        $hod->staffProfile()->create(['department_id' => Department::factory()->create(['code' => 'LANG'])->id]);
        $outsider->staffProfile()->create(['department_id' => Department::factory()->create(['code' => 'EXEC'])->id]);

        $this->actingAs($hod)
            ->get(route('ict.accounts.duties.index', $outsider))
            ->assertForbidden();

        $this->actingAs($hod)
            ->get(route('ict.team-duties.index'))
            ->assertOk()
            ->assertDontSee($outsider->name);
    }

    public function test_a_plain_staff_member_cannot_reach_the_team_duties_page(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($staff)
            ->get(route('ict.team-duties.index'))
            ->assertForbidden();
    }

    private function registryHeadOfHr(): User
    {
        $lead = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $this->grantDuty($lead, DutyCatalog::REGISTRY_HEAD_REVIEW);

        return $lead;
    }

    private function revokeDuty(User $user, string $code): void
    {
        $duty = DutyCatalog::ensureDuty($code);

        $user->duties()->detach($duty->getKey());
        $user->unsetRelation('duties');
    }

    private function grantDuty(User $user, string $code): void
    {
        $duty = DutyCatalog::ensureDuty($code);

        if (! $user->duties()->whereKey($duty->getKey())->exists()) {
            $user->duties()->attach($duty->getKey(), ['assigned_at' => now()]);
        }

        $user->unsetRelation('duties');
    }

    private function sameDepartment(User $a, User $b): void
    {
        $department = Department::factory()->create(['code' => 'REG', 'name' => 'Registry Test Department']);

        StaffProfile::factory()->create(['user_id' => $a->id, 'department_id' => $department->id]);
        StaffProfile::factory()->create(['user_id' => $b->id, 'department_id' => $department->id]);

        $a->unsetRelation('profile');
        $b->unsetRelation('profile');
    }
}
