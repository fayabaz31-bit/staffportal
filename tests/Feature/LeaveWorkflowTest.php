<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\OfficialLetter;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $hod;

    private User $hrAdmin;

    private User $director;

    private Department $dept;

    private LeaveType $annualLeave;

    private LeaveType $sickLeave;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $this->dept = Department::factory()->create([
            'name' => 'Department of French Studies',
            'code' => 'DFS',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role' => UserRole::Staff,
            'status' => 'active',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
        ]);

        $this->hod = User::factory()->create([
            'role' => UserRole::HeadOfDepartment,
            'status' => 'active',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->hod->id,
            'department_id' => $this->dept->id,
        ]);

        $this->hrAdmin = User::factory()->create([
            'role' => UserRole::HrRegistryAdmin,
            'status' => 'active',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->hrAdmin->id,
            'department_id' => $this->dept->id,
        ]);

        $this->director = User::factory()->create([
            'role' => UserRole::Director,
            'status' => 'active',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->director->id,
            'department_id' => $this->dept->id,
        ]);

        $this->annualLeave = LeaveType::create([
            'name' => 'Annual Leave',
            'code' => 'ANNUAL',
            'description' => 'Standard statutory annual vacation leave',
            'default_days_per_year' => 30,
            'requires_attachment' => false,
            'requires_supervisor_review' => true,
            'requires_hr_verification' => true,
            'is_active' => true,
        ]);

        $this->sickLeave = LeaveType::create([
            'name' => 'Sick / Medical Leave',
            'code' => 'SICK',
            'description' => 'Medical leave requiring health clinic certificate',
            'default_days_per_year' => 14,
            'requires_attachment' => true,
            'requires_supervisor_review' => true,
            'requires_hr_verification' => true,
            'is_active' => true,
        ]);
    }

    public function test_staff_can_view_leave_dashboard_with_balances_and_policy_status(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => (int) Carbon::now()->year,
            'allocated_days' => 30,
            'used_days' => 5,
            'pending_days' => 0,
            'is_policy_configured' => true,
        ]);

        $response = $this->actingAs($this->staff)->get(route('leave.index'));

        $response->assertOk();
        $response->assertSee('Leave Management');
        $response->assertSee('Annual Leave');
        $response->assertSee('25'); // 30 - 5 remaining
        $response->assertSee('CONFIGURED');
    }

    public function test_unconfigured_policy_alert_displayed_when_entitlements_unconfigured(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => (int) Carbon::now()->year,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 0,
            'is_policy_configured' => false, // unconfigured institutional policy
        ]);

        $response = $this->actingAs($this->staff)->get(route('leave.index'));

        $response->assertOk();
        $response->assertSee('Institutional Policy Notice:');
        $response->assertSee('UNCONFIGURED / DRAFT');
    }

    public function test_staff_cannot_apply_with_insufficient_leave_balance(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 5,
            'used_days' => 4,
            'pending_days' => 0,
            'is_policy_configured' => true,
        ]);

        // Request 5 days (e.g., Monday 2 Nov 2026 to Friday 6 Nov 2026), but only 1 day remaining
        $response = $this->actingAs($this->staff)->post(route('leave.store'), [
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'reason' => 'Annual family break.',
        ]);

        $response->assertSessionHasErrors('leave_type_id');
        $this->assertDatabaseEmpty('leave_applications');
    }

    public function test_business_days_exclude_weekends_in_duration_calculation(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 0,
            'is_policy_configured' => true,
        ]);

        // Friday 6 Nov 2026 to Monday 9 Nov 2026 = 4 calendar days, but only 2 business days (Fri & Mon)
        $response = $this->actingAs($this->staff)->post(route('leave.store'), [
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-06',
            'end_date' => '2026-11-09',
            'reason' => 'Weekend travel with extension.',
        ]);

        $response->assertRedirect(route('leave.index'));
        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $this->staff->id,
            'days_requested' => 2,
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
        ]);

        // Pending days reserved on entitlement
        $this->assertDatabaseHas('leave_entitlements', [
            'user_id' => $this->staff->id,
            'pending_days' => 2,
        ]);
    }

    public function test_staff_cannot_apply_with_overlapping_leave_dates(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 5,
            'is_policy_configured' => true,
        ]);

        // Existing pending leave from 2 Nov to 6 Nov
        LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'First vacation request.',
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 1,
        ]);

        // Overlapping request: 4 Nov to 10 Nov
        $response = $this->actingAs($this->staff)->post(route('leave.store'), [
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-04',
            'end_date' => '2026-11-10',
            'reason' => 'Overlapping request.',
        ]);

        $response->assertSessionHasErrors('start_date');
        $this->assertEquals(1, LeaveApplication::where('user_id', $this->staff->id)->count());
    }

    public function test_attachment_required_when_leave_type_mandates_it(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->sickLeave->id,
            'year' => 2026,
            'allocated_days' => 14,
            'used_days' => 0,
            'pending_days' => 0,
            'is_policy_configured' => true,
        ]);

        // Attempt submission without attachment
        $response = $this->actingAs($this->staff)->post(route('leave.store'), [
            'leave_type_id' => $this->sickLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'reason' => 'Medical recovery from outpatient procedure.',
        ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseEmpty('leave_applications');

        // Submit with valid attachment
        $file = UploadedFile::fake()->create('medical_cert.pdf', 300, 'application/pdf');
        $responseWithFile = $this->actingAs($this->staff)->post(route('leave.store'), [
            'leave_type_id' => $this->sickLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'reason' => 'Medical recovery from outpatient procedure.',
            'attachment' => $file,
        ]);

        $responseWithFile->assertRedirect(route('leave.index'));
        $this->assertDatabaseHas('leave_applications', [
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->sickLeave->id,
            'days_requested' => 3,
        ]);
    }

    public function test_staff_can_cancel_pending_leave_application_and_restore_balance(): void
    {
        $entitlement = LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 5,
            'is_policy_configured' => true,
        ]);

        $application = LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'Trip to Abuja.',
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 1,
        ]);

        $response = $this->actingAs($this->staff)->post(route('leave.cancel', $application));

        $response->assertRedirect(route('leave.index'));
        $application->refresh();
        $this->assertEquals(LeaveApplication::STATUS_CANCELLED, $application->status);
        $this->assertNotNull($application->cancelled_at);

        $entitlement->refresh();
        $this->assertEquals(0, $entitlement->pending_days);
    }

    public function test_staff_cannot_cancel_other_users_leave_application(): void
    {
        $otherStaff = User::factory()->create(['role' => UserRole::Staff, 'status' => 'active']);

        $application = LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'Trip to Abuja.',
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 1,
        ]);

        $response = $this->actingAs($otherStaff)->post(route('leave.cancel', $application));
        $response->assertForbidden();
    }

    public function test_multi_stage_approval_state_machine_advances_correctly(): void
    {
        $entitlement = LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 5,
            'is_policy_configured' => true,
        ]);

        $application = LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'Annual leave vacation request.',
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 1,
        ]);

        // Stage 1: HOD reviews and recommends
        $hodResponse = $this->actingAs($this->hod)->post(route('approvals.leave.action', $application), [
            'action' => 'recommend',
            'comments' => 'Recommended. Work handover arranged with Mr. Ade.',
            'concurrency_version' => 1,
        ]);

        $hodResponse->assertRedirect(route('approvals.leave.index'));
        $application->refresh();
        $this->assertEquals(LeaveApplication::STAGE_HR, $application->current_stage);
        $this->assertEquals(LeaveApplication::STATUS_PENDING_HR, $application->status);
        $this->assertEquals(2, $application->concurrency_version);

        // Stage 2: HR Administrator verifies against records
        $hrResponse = $this->actingAs($this->hrAdmin)->post(route('approvals.leave.action', $application), [
            'action' => 'verify',
            'comments' => 'Entitlement verified against registry records.',
            'concurrency_version' => 2,
        ]);

        $hrResponse->assertRedirect(route('approvals.leave.index'));
        $application->refresh();
        $this->assertEquals(LeaveApplication::STAGE_DIRECTOR, $application->current_stage);
        $this->assertEquals(LeaveApplication::STATUS_PENDING_DIRECTOR, $application->status);
        $this->assertEquals(3, $application->concurrency_version);

        // Stage 3: Director issues final approval
        $directorResponse = $this->actingAs($this->director)->post(route('approvals.leave.action', $application), [
            'action' => 'approve',
            'comments' => 'Approved as recommended.',
            'concurrency_version' => 3,
        ]);

        $directorResponse->assertRedirect(route('approvals.leave.index'));
        $application->refresh();
        $this->assertEquals(LeaveApplication::STATUS_APPROVED, $application->status);
        $this->assertEquals(LeaveApplication::STAGE_COMPLETED, $application->current_stage);
        $this->assertEquals($this->director->id, $application->approved_by);

        // Entitlement balance was deducted
        $entitlement->refresh();
        $this->assertEquals(0, $entitlement->pending_days);
        $this->assertEquals(5, $entitlement->used_days);
        $this->assertEquals(25, $entitlement->remainingDays());

        // Official letter record generated
        $letter = OfficialLetter::where('leave_application_id', $application->id)->first();
        $this->assertNotNull($letter);
        $this->assertStringStartsWith('NFLV/EST/LEAVE/2026/', $letter->reference_number);
        $this->assertEquals($this->staff->id, $letter->user_id);
        $this->assertTrue(Storage::disk('private')->exists($letter->storage_path));
    }

    public function test_director_approval_is_blocked_when_leave_policy_is_unconfigured(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 5,
            'is_policy_configured' => false, // UNCONFIGURED POLICY
        ]);

        $application = LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'Annual leave vacation request.',
            'status' => LeaveApplication::STATUS_PENDING_DIRECTOR,
            'current_stage' => LeaveApplication::STAGE_DIRECTOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 1,
        ]);

        $response = $this->actingAs($this->director)->post(route('approvals.leave.action', $application), [
            'action' => 'approve',
            'comments' => 'Attempting approval.',
            'concurrency_version' => 1,
        ]);

        $response->assertSessionHas('error');
        $application->refresh();
        $this->assertNotEquals(LeaveApplication::STATUS_APPROVED, $application->status);
        $this->assertEquals(LeaveApplication::STATUS_PENDING_DIRECTOR, $application->status);
        $this->assertDatabaseEmpty('official_letters');
    }

    public function test_cross_department_hod_cannot_act_on_another_departments_leave(): void
    {
        $otherDept = Department::factory()->create(['name' => 'ICT Directorate', 'code' => 'ICT']);
        $otherHod = User::factory()->create(['role' => UserRole::HeadOfDepartment, 'status' => 'active']);
        StaffProfile::factory()->create(['user_id' => $otherHod->id, 'department_id' => $otherDept->id]);

        $application = LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'Annual leave.',
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 1,
        ]);

        $response = $this->actingAs($otherHod)->post(route('approvals.leave.action', $application), [
            'action' => 'recommend',
            'comments' => 'Cross-department attempt',
            'concurrency_version' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_rejection_restores_pending_days_and_saves_rejection_reason(): void
    {
        $entitlement = LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 5,
            'is_policy_configured' => true,
        ]);

        $application = LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'Annual leave.',
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 1,
        ]);

        $response = $this->actingAs($this->hod)->post(route('approvals.leave.action', $application), [
            'action' => 'reject',
            'comments' => 'Cannot approve at this time due to exam moderation duties.',
            'concurrency_version' => 1,
        ]);

        $response->assertRedirect(route('approvals.leave.index'));
        $application->refresh();
        $this->assertEquals(LeaveApplication::STATUS_REJECTED, $application->status);
        $this->assertEquals('Cannot approve at this time due to exam moderation duties.', $application->rejection_reason);

        $entitlement->refresh();
        $this->assertEquals(0, $entitlement->pending_days);
    }

    public function test_concurrency_conflict_detected_and_aborted_when_version_mismatches(): void
    {
        LeaveEntitlement::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 5,
            'is_policy_configured' => true,
        ]);

        $application = LeaveApplication::create([
            'user_id' => $this->staff->id,
            'leave_type_id' => $this->annualLeave->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'days_requested' => 5,
            'reason' => 'Annual leave.',
            'status' => LeaveApplication::STATUS_PENDING_SUPERVISOR,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => $this->dept->id,
            'concurrency_version' => 2, // version is 2
        ]);

        // Attempt action with outdated version 1
        $response = $this->actingAs($this->hod)->post(route('approvals.leave.action', $application), [
            'action' => 'recommend',
            'comments' => 'Old form submission',
            'concurrency_version' => 1,
        ]);

        $response->assertSessionHas('error');
        $application->refresh();
        $this->assertEquals(LeaveApplication::STAGE_SUPERVISOR, $application->current_stage);
    }

    public function test_official_letter_can_be_downloaded_by_applicant_and_denied_to_other_staff(): void
    {
        $fakePdf = '%PDF-1.4 Fake PDF Content';
        Storage::disk('private')->put('letters/2026/test_letter.pdf', $fakePdf);

        $letter = OfficialLetter::create([
            'user_id' => $this->staff->id,
            'reference_number' => 'NFLV/EST/LEAVE/2026/0001',
            'document_type' => 'leave_approval',
            'subject' => 'Approval of Annual Leave Application',
            'body_html' => '<p>Approved</p>',
            'storage_path' => 'letters/2026/test_letter.pdf',
            'file_hash' => hash('sha256', $fakePdf),
            'file_size' => strlen($fakePdf),
            'issued_by' => $this->director->id,
            'issued_at' => now(),
        ]);

        // 1. Applicant can download
        $ownerResponse = $this->actingAs($this->staff)->get(route('letters.download', $letter));
        $ownerResponse->assertOk();
        $ownerResponse->assertHeader('Content-Type', 'application/pdf');

        // 2. Director can download
        $directorResponse = $this->actingAs($this->director)->get(route('letters.download', $letter));
        $directorResponse->assertOk();

        // 3. Another staff member is denied (Anti-IDOR)
        $unrelatedStaff = User::factory()->create(['role' => UserRole::Staff, 'status' => 'active']);
        $deniedResponse = $this->actingAs($unrelatedStaff)->get(route('letters.download', $letter));
        $deniedResponse->assertForbidden();
    }
}
