<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\Delegation;
use App\Models\Department;
use App\Models\OrganisationalUnit;
use App\Models\ReportingRelationship;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Registry hierarchy is configuration, not code: units, reporting lines and
 * time-bounded delegations are stored as data and audited.
 */
class OrganisationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_authorised_administrators_can_view_or_change_the_hierarchy(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($staff)->get(route('registry.organisation.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('registry.organisation.units.store'), [
            'department_id' => Department::factory()->create()->id,
            'name' => 'Unauthorised Unit',
            'code' => 'UNAUTH',
        ])->assertForbidden();
    }

    public function test_a_registrar_can_create_units_and_reporting_lines(): void
    {
        $registrar = $this->registrar();
        $department = Department::factory()->create(['code' => 'REGISTRY', 'name' => 'Registry']);
        $officer = User::factory()->create(['role' => UserRole::Staff]);
        $supervisor = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($registrar)->get(route('registry.organisation.index'))->assertOk();

        $this->actingAs($registrar)->post(route('registry.organisation.units.store'), [
            'department_id' => $department->id,
            'name' => 'Personnel Unit',
            'code' => 'reg-personnel',
            'head_user_id' => $supervisor->id,
            'sort_order' => 10,
        ])->assertRedirect();

        $unit = OrganisationalUnit::query()->sole();
        $this->assertSame('REG-PERSONNEL', $unit->code);
        $this->assertSame($supervisor->id, $unit->head_user_id);

        $this->actingAs($registrar)->post(route('registry.organisation.reporting.store'), [
            'subordinate_id' => $officer->id,
            'supervisor_id' => $supervisor->id,
            'organisational_unit_id' => $unit->id,
            'relationship_type' => ReportingRelationship::LINE_MANAGER,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_primary' => true,
        ])->assertRedirect();

        $this->assertTrue($supervisor->fresh()->isSupervisorOf($officer->fresh()));
        $this->assertDatabaseHas('audit_logs', ['action' => 'organisation.reporting_created']);
    }

    public function test_overlapping_reporting_lines_are_rejected(): void
    {
        $registrar = $this->registrar();
        $officer = User::factory()->create(['role' => UserRole::Staff]);
        $supervisor = User::factory()->create(['role' => UserRole::Staff]);

        ReportingRelationship::query()->create([
            'subordinate_id' => $officer->id,
            'supervisor_id' => $supervisor->id,
            'relationship_type' => ReportingRelationship::LINE_MANAGER,
            'effective_from' => now()->subYear()->toDateString(),
            'is_primary' => true,
            'created_by' => $registrar->id,
        ]);

        $this->actingAs($registrar)->post(route('registry.organisation.reporting.store'), [
            'subordinate_id' => $officer->id,
            'supervisor_id' => $supervisor->id,
            'relationship_type' => ReportingRelationship::LINE_MANAGER,
            'effective_from' => now()->toDateString(),
        ])->assertSessionHasErrors('effective_from');

        $this->assertSame(1, ReportingRelationship::query()->count());
    }

    public function test_a_time_bounded_delegation_grants_the_scope_only_while_active(): void
    {
        $registrar = $this->registrar();
        $delegate = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        StaffProfile::factory()->create([
            'user_id' => $delegate->id,
            'department_id' => Department::factory()->create(['code' => 'REGISTRY', 'name' => 'Registry'])->id,
        ]);

        $this->actingAs($registrar)->post(route('registry.organisation.delegations.store'), [
            'delegator_id' => $registrar->id,
            'delegate_id' => $delegate->id,
            'scope' => DutyCatalog::REGISTRY_REGISTRAR_APPROVE,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addWeek()->toDateString(),
            'reason' => 'Registrar on official leave.',
        ])->assertRedirect();

        $delegation = Delegation::query()->sole();
        $this->assertTrue($delegate->fresh()->hasActiveDelegationFor(DutyCatalog::REGISTRY_REGISTRAR_APPROVE));

        $this->actingAs($registrar)
            ->patch(route('registry.organisation.delegations.revoke', $delegation))
            ->assertRedirect();

        $this->assertNotNull($delegation->fresh()->revoked_at);
        $this->assertFalse($delegate->fresh()->hasActiveDelegationFor(DutyCatalog::REGISTRY_REGISTRAR_APPROVE));
    }

    public function test_a_technical_administrator_cannot_create_registry_correspondence(): void
    {
        $ict = User::factory()->create(['role' => UserRole::IctSuperadmin]);

        $this->actingAs($ict)->get(route('registry.correspondence.create'))->assertForbidden();
    }

    private function registrar(): User
    {
        $registrar = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $duty = DutyCatalog::ensureDuty(DutyCatalog::REGISTRY_REGISTRAR_APPROVE);

        if (! $registrar->duties()->whereKey($duty->getKey())->exists()) {
            $registrar->duties()->attach($duty->getKey(), ['assigned_at' => now()]);
        }

        return $registrar->fresh();
    }
}
