<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\CorrespondenceCategory;
use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\Memo;
use App\Models\PayslipBatch;
use App\Models\ProfileChangeRequest;
use App\Models\ServiceRequest;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\CorrespondenceService;
use App\Support\DashboardMetrics;
use App\Support\RegistryWorkflowCatalog;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_role_renders_a_dashboard_with_real_figures(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role->value]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSeeText('Key figures')
                ->assertSeeText($role->dashboardTitle());
        }
    }

    public function test_metrics_react_to_real_records_for_the_registry_dashboard(): void
    {
        $registry = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);

        ProfileChangeRequest::factory()->count(3)->create([
            'status' => ProfileChangeRequest::STATUS_PENDING,
        ]);

        $metrics = (new DashboardMetrics($registry))->forRole();

        $pendingTile = collect($metrics['kpis'])->firstWhere('label', 'Pending profile changes');
        $this->assertNotNull($pendingTile);
        $this->assertSame(3, $pendingTile['value']);

        $queueItem = collect($metrics['actionQueue'])->firstWhere('label', 'Profile-change requests to review');
        $this->assertNotNull($queueItem);
        $this->assertSame(3, $queueItem['count']);
    }

    public function test_a_service_desk_figure_is_scoped_to_its_own_unit(): void
    {
        $registry = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);

        foreach (range(1, 2) as $i) {
            $this->createServiceRequest(ServiceRequest::UNIT_REGISTRY, ServiceRequest::STATUS_OPEN);
        }
        foreach (range(1, 5) as $i) {
            $this->createServiceRequest(ServiceRequest::UNIT_ICT, ServiceRequest::STATUS_OPEN);
        }

        $metrics = (new DashboardMetrics($registry))->forRole();

        $tile = collect($metrics['kpis'])->firstWhere('label', 'Open registry service requests');
        $this->assertNotNull($tile);
        $this->assertSame(2, $tile['value'], 'The registry desk must not count ICT tickets.');
    }

    public function test_bursary_figures_split_batch_states(): void
    {
        $bursary = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        PayslipBatch::factory()->count(2)->create(['status' => PayslipBatch::STATUS_STAGED]);
        PayslipBatch::factory()->create(['status' => PayslipBatch::STATUS_QUARANTINED]);
        PayslipBatch::factory()->count(4)->create(['status' => PayslipBatch::STATUS_PUBLISHED]);

        $metrics = (new DashboardMetrics($bursary))->forRole();

        $this->assertSame(2, collect($metrics['kpis'])->firstWhere('label', 'Batches awaiting release')['value']);
        $this->assertSame(1, collect($metrics['kpis'])->firstWhere('label', 'Quarantined')['value']);

        $chart = collect($metrics['charts'])->firstWhere('title', 'Payslip batch states');
        $this->assertNotNull($chart);
        $this->assertSame([2, 1, 4, 0], $chart['series'][0]['values']);
    }

    public function test_head_of_department_metrics_are_scoped_to_their_department(): void
    {
        $myDepartment = Department::factory()->create(['code' => 'LANG']);
        $otherDepartment = Department::factory()->create(['code' => 'SCI']);

        $hod = User::factory()->create(['role' => UserRole::HeadOfDepartment]);
        StaffProfile::factory()->create(['user_id' => $hod->id, 'department_id' => $myDepartment->id]);

        $mine = User::factory()->create();
        StaffProfile::factory()->create(['user_id' => $mine->id, 'department_id' => $myDepartment->id]);

        $theirs = User::factory()->create();
        StaffProfile::factory()->create(['user_id' => $theirs->id, 'department_id' => $otherDepartment->id]);

        LeaveApplication::factory()->create([
            'user_id' => $mine->id,
            'assigned_department_id' => $myDepartment->id,
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
        ]);
        LeaveApplication::factory()->create([
            'user_id' => $theirs->id,
            'assigned_department_id' => $otherDepartment->id,
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
        ]);

        $metrics = (new DashboardMetrics($hod->fresh()))->forRole();

        $this->assertSame(
            1,
            collect($metrics['kpis'])->firstWhere('label', 'Awaiting recommendation')['value'],
            'A department lead must only see their own department.'
        );
        $this->assertSame(2, collect($metrics['kpis'])->firstWhere('label', 'Team size')['value']);
    }

    public function test_empty_work_items_are_hidden_from_the_action_queue(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $metrics = (new DashboardMetrics($staff))->forRole();

        foreach ($metrics['actionQueue'] as $item) {
            $this->assertGreaterThan(0, $item['count'], 'Zero-count work items must not be queued.');
        }
    }

    public function test_director_sees_the_approval_pipeline_figures(): void
    {
        $director = User::factory()->create(['role' => UserRole::Director]);

        LeaveApplication::factory()->count(2)->create(['status' => LeaveApplication::STATUS_PENDING_DIRECTOR]);
        LeaveApplication::factory()->count(3)->create(['status' => LeaveApplication::STATUS_APPROVED]);
        LeaveApplication::factory()->create(['status' => LeaveApplication::STATUS_REJECTED]);
        $this->createPublishedMemo();
        $this->createPublishedMemo();

        $metrics = (new DashboardMetrics($director))->forRole();

        $this->assertSame(2, collect($metrics['kpis'])->firstWhere('label', 'Awaiting your decision')['value']);
        $this->assertSame(2, collect($metrics['kpis'])->firstWhere('label', 'Published memos')['value']);

        $chart = collect($metrics['charts'])->firstWhere('title', 'Institutional approval pipeline');
        $this->assertSame([0, 0, 2, 3, 1], $chart['series'][0]['values']);
    }

    public function test_only_leads_and_account_managers_are_offered_duty_assignment(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $hod = User::factory()->create(['role' => UserRole::HeadOfDepartment]);

        $this->assertFalse((new DashboardMetrics($staff))->canAssignDuties());
        $this->assertTrue((new DashboardMetrics($hod))->canAssignDuties());
    }

    public function test_the_dashboard_shows_an_accessible_figure_table_for_charts(): void
    {
        $ict = User::factory()->create(['role' => UserRole::IctSuperadmin]);

        $this->actingAs($ict)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('chart-data-table', escape: false)
            ->assertSee('data-chart-payload', escape: false);
    }

    public function test_the_registry_dashboard_follows_the_configured_approval_stage_per_desk(): void
    {
        RegistryWorkflowCatalog::sync();
        $department = Department::factory()->create(['code' => 'REGISTRY', 'name' => 'Registry']);

        $officer = $this->registryOfficer($department);
        $head = $this->registryOfficer($department, DutyCatalog::REGISTRY_HEAD_REVIEW);
        $registrar = $this->registryOfficer($department, DutyCatalog::REGISTRY_REGISTRAR_APPROVE);

        $category = CorrespondenceCategory::query()->where('code', 'annual_increment')->firstOrFail();
        $service = app(CorrespondenceService::class);
        $document = $service->create($officer, [
            'category_id' => $category->id,
            'subject' => 'Increment confirmation',
            'body' => '<p>Approved increment.</p>',
        ]);
        $service->submit($document, $officer);

        $headMetrics = (new DashboardMetrics($head->fresh()))->forRole();
        $registrarMetrics = (new DashboardMetrics($registrar->fresh()))->forRole();
        $officerMetrics = (new DashboardMetrics($officer->fresh()))->forRole();

        $this->assertSame(1, collect($headMetrics['kpis'])->firstWhere('label', 'Correspondence for Head of HR review')['value']);
        $this->assertSame(0, collect($registrarMetrics['kpis'])->firstWhere('label', 'Correspondence awaiting Registrar approval')['value']);
        $this->assertSame(0, collect($officerMetrics['kpis'])->firstWhere('label', 'My open duty assignments')['value']);

        $service->decide($document->fresh(), $head, 'recommend', 'Recommended.');

        $registrarMetrics = (new DashboardMetrics($registrar->fresh()))->forRole();
        $headMetrics = (new DashboardMetrics($head->fresh()))->forRole();

        $this->assertSame(1, collect($registrarMetrics['kpis'])->firstWhere('label', 'Correspondence awaiting Registrar approval')['value']);
        $this->assertSame(0, collect($headMetrics['kpis'])->firstWhere('label', 'Correspondence for Head of HR review')['value']);
    }

    public function test_the_director_dashboard_counts_only_reserved_matters(): void
    {
        RegistryWorkflowCatalog::sync();
        $department = Department::factory()->create(['code' => 'REGISTRY', 'name' => 'Registry']);

        $officer = $this->registryOfficer($department);
        $head = $this->registryOfficer($department, DutyCatalog::REGISTRY_HEAD_REVIEW);
        $registrar = $this->registryOfficer($department, DutyCatalog::REGISTRY_REGISTRAR_APPROVE);
        $director = User::factory()->create(['role' => UserRole::Director]);
        StaffProfile::factory()->create([
            'user_id' => $director->id,
            'department_id' => Department::factory()->create(['code' => 'DIRECTORATE', 'name' => 'Directorate'])->id,
        ]);
        $this->grantDuty($director, DutyCatalog::REGISTRY_DIRECTOR_ESCALATION);

        $category = CorrespondenceCategory::query()->where('code', 'promotion')->firstOrFail();
        $service = app(CorrespondenceService::class);
        $document = $service->create($officer, [
            'category_id' => $category->id,
            'subject' => 'Promotion confirmation',
            'body' => '<p>Promotion approved.</p>',
        ]);
        $service->submit($document, $officer);

        $emptyMetrics = (new DashboardMetrics($director->fresh()))->forRole();
        $this->assertSame(0, collect($emptyMetrics['kpis'])->firstWhere('label', 'Reserved correspondence')['value']);

        $service->decide($document->fresh(), $head, 'review', 'Reviewed.');
        $service->decide($document->fresh(), $registrar, 'recommend', 'Recommended.');

        $metrics = (new DashboardMetrics($director->fresh()))->forRole();

        $this->assertSame(1, collect($metrics['kpis'])->firstWhere('label', 'Reserved correspondence')['value']);
        $this->assertNotNull(collect($metrics['actionQueue'])->firstWhere('label', 'Reserved correspondence awaiting your decision'));
    }

    private function registryOfficer(Department $department, ?string $duty = null): User
    {
        $user = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        StaffProfile::factory()->create(['user_id' => $user->id, 'department_id' => $department->id]);

        if ($duty !== null) {
            $this->grantDuty($user, $duty);
        }

        return $user->fresh();
    }

    private function grantDuty(User $user, string $code): void
    {
        $duty = DutyCatalog::ensureDuty($code);

        if (! $user->duties()->whereKey($duty->getKey())->exists()) {
            $user->duties()->attach($duty->getKey(), ['assigned_at' => now()]);
        }

        $user->unsetRelation('duties');
    }

    private function createServiceRequest(string $unit, string $status): ServiceRequest
    {
        return ServiceRequest::create([
            'tracking_reference' => 'REQ-'.fake()->unique()->numerify('####-####'),
            'user_id' => User::factory()->create()->id,
            'unit' => $unit,
            'category' => 'general',
            'subject' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'priority' => ServiceRequest::PRIORITY_STANDARD,
            'status' => $status,
            'is_confidential' => false,
        ]);
    }

    private function createPublishedMemo(): Memo
    {
        return Memo::create([
            'author_id' => User::factory()->create()->id,
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'classification' => Memo::CLASSIFICATION_NORMAL,
            'status' => Memo::STATUS_PUBLISHED,
            'audience_type' => 'institution',
            'published_at' => now(),
        ]);
    }
}
