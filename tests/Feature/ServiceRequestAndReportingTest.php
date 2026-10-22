<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceRequestAndReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $staffA;

    private User $staffB;

    private User $hrAdmin;

    private User $bursaryAdmin;

    private User $hod;

    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $this->dept = Department::factory()->create([
            'name' => 'Department of Linguistics',
            'code' => 'LIN',
            'is_active' => true,
        ]);

        $this->staffA = User::factory()->create([
            'role' => UserRole::Staff,
            'status' => 'active',
            'name' => 'Staff Alice',
            'staff_number' => 'NFLV-1001',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->staffA->id,
            'department_id' => $this->dept->id,
        ]);

        $this->staffB = User::factory()->create([
            'role' => UserRole::Staff,
            'status' => 'active',
            'name' => 'Staff Bob',
            'staff_number' => 'NFLV-1002',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->staffB->id,
            'department_id' => $this->dept->id,
        ]);

        $this->hrAdmin = User::factory()->create([
            'role' => UserRole::HrRegistryAdmin,
            'status' => 'active',
            'name' => 'HR Administrator',
            'staff_number' => 'NFLV-2001',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->hrAdmin->id,
            'department_id' => $this->dept->id,
        ]);

        $this->bursaryAdmin = User::factory()->create([
            'role' => UserRole::BursaryAdmin,
            'status' => 'active',
            'name' => 'Bursary Officer',
            'staff_number' => 'NFLV-3001',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->bursaryAdmin->id,
            'department_id' => $this->dept->id,
        ]);

        $this->hod = User::factory()->create([
            'role' => UserRole::HeadOfDepartment,
            'status' => 'active',
            'name' => 'Head of Department',
            'staff_number' => 'NFLV-4001',
        ]);
        StaffProfile::factory()->create([
            'user_id' => $this->hod->id,
            'department_id' => $this->dept->id,
        ]);
    }

    public function test_staff_can_view_their_service_requests_and_submit_new_request(): void
    {
        $response = $this->actingAs($this->staffA)->get(route('service-requests.index'));
        $response->assertOk();
        $response->assertSee('My Service Requests');

        // Submit new request
        $createResponse = $this->actingAs($this->staffA)->post(route('service-requests.store'), [
            'unit' => 'registry',
            'category' => 'employment_verification',
            'subject' => 'Request for Official Employment Attestation',
            'description' => 'I require an official letter confirming my tenure for my mortgage application.',
            'priority' => 'standard',
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('service_requests', [
            'user_id' => $this->staffA->id,
            'unit' => 'registry',
            'category' => 'employment_verification',
            'status' => ServiceRequest::STATUS_OPEN,
            'is_confidential' => false,
        ]);

        $request = ServiceRequest::where('user_id', $this->staffA->id)->first();
        $this->assertStringStartsWith('REQ-2026-', $request->tracking_reference);

        // Initial message and status log created
        $this->assertDatabaseHas('service_request_messages', [
            'service_request_id' => $request->id,
            'user_id' => $this->staffA->id,
            'is_internal' => false,
        ]);

        $this->assertDatabaseHas('service_request_status_logs', [
            'service_request_id' => $request->id,
            'to_status' => ServiceRequest::STATUS_OPEN,
        ]);
    }

    public function test_salary_and_banking_enquiries_are_automatically_marked_confidential(): void
    {
        $response = $this->actingAs($this->staffA)->post(route('service-requests.store'), [
            'unit' => 'bursary',
            'category' => 'salary_enquiry',
            'subject' => 'Clarification on October 2026 Salary Deductions',
            'description' => 'Kindly clarify the pension and cooperative deductions appearing on my payslip.',
            'priority' => 'high',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_requests', [
            'user_id' => $this->staffA->id,
            'unit' => 'bursary',
            'is_confidential' => true,
        ]);
    }

    public function test_cross_unit_isolation_registry_admin_cannot_access_or_manage_bursary_requests(): void
    {
        $bursaryRequest = ServiceRequest::create([
            'tracking_reference' => 'REQ-2026-0099',
            'user_id' => $this->staffA->id,
            'unit' => 'bursary',
            'category' => 'salary_enquiry',
            'subject' => 'Confidential Salary Case',
            'description' => 'Salary query',
            'priority' => 'high',
            'status' => 'open',
            'is_confidential' => true,
        ]);

        // HR Admin tries to view Bursary unit queue
        $queueResponse = $this->actingAs($this->hrAdmin)->get(route('unit.service-requests.index', 'bursary'));
        $queueResponse->assertForbidden();

        // HR Admin tries to view specific Bursary request thread
        $threadResponse = $this->actingAs($this->hrAdmin)->get(route('service-requests.show', $bursaryRequest));
        $threadResponse->assertForbidden();
    }

    public function test_cross_unit_isolation_bursary_admin_cannot_access_or_manage_registry_requests(): void
    {
        $registryRequest = ServiceRequest::create([
            'tracking_reference' => 'REQ-2026-0088',
            'user_id' => $this->staffA->id,
            'unit' => 'registry',
            'category' => 'service_record',
            'subject' => 'Service Record Request',
            'description' => 'Registry query',
            'priority' => 'standard',
            'status' => 'open',
            'is_confidential' => false,
        ]);

        // Bursary Admin tries to view Registry unit queue
        $queueResponse = $this->actingAs($this->bursaryAdmin)->get(route('unit.service-requests.index', 'registry'));
        $queueResponse->assertForbidden();

        // Bursary Admin tries to view specific Registry request thread
        $threadResponse = $this->actingAs($this->bursaryAdmin)->get(route('service-requests.show', $registryRequest));
        $threadResponse->assertForbidden();
    }

    public function test_unrelated_staff_cannot_view_another_staff_members_service_request(): void
    {
        $request = ServiceRequest::create([
            'tracking_reference' => 'REQ-2026-0001',
            'user_id' => $this->staffA->id,
            'unit' => 'registry',
            'category' => 'general_registry',
            'subject' => 'Alice Private Request',
            'description' => 'Personal request details',
            'priority' => 'standard',
            'status' => 'open',
        ]);

        // Staff B tries to open Staff A's request
        $response = $this->actingAs($this->staffB)->get(route('service-requests.show', $request));
        $response->assertForbidden();
    }

    public function test_applicant_cannot_view_internal_notes_in_conversation_thread(): void
    {
        $request = ServiceRequest::create([
            'tracking_reference' => 'REQ-2026-0002',
            'user_id' => $this->staffA->id,
            'unit' => 'registry',
            'category' => 'employment_verification',
            'subject' => 'Employment Verification',
            'description' => 'Applicant message',
            'priority' => 'standard',
            'status' => 'open',
        ]);

        // 1. Applicant message
        ServiceRequestMessage::create([
            'service_request_id' => $request->id,
            'user_id' => $this->staffA->id,
            'message' => 'Please expedite this attestation.',
            'is_internal' => false,
        ]);

        // 2. Unit handler posts an INTERNAL note
        ServiceRequestMessage::create([
            'service_request_id' => $request->id,
            'user_id' => $this->hrAdmin->id,
            'message' => 'INTERNAL NOTE: Pending confirmation of disciplinary clearance.',
            'is_internal' => true,
        ]);

        // 3. Unit handler posts a public response
        ServiceRequestMessage::create([
            'service_request_id' => $request->id,
            'user_id' => $this->hrAdmin->id,
            'message' => 'We are processing your attestation letter.',
            'is_internal' => false,
        ]);

        // Handler views page -> sees both internal note and public messages
        $handlerResponse = $this->actingAs($this->hrAdmin)->get(route('service-requests.show', $request));
        $handlerResponse->assertOk();
        $handlerResponse->assertSee('INTERNAL NOTE: Pending confirmation of disciplinary clearance.');
        $handlerResponse->assertSee('We are processing your attestation letter.');

        // Applicant views page -> DOES NOT SEE the internal note!
        $applicantResponse = $this->actingAs($this->staffA)->get(route('service-requests.show', $request));
        $applicantResponse->assertOk();
        $applicantResponse->assertDontSee('INTERNAL NOTE: Pending confirmation of disciplinary clearance.');
        $applicantResponse->assertSee('We are processing your attestation letter.');
    }

    public function test_applicant_cannot_post_internal_notes(): void
    {
        $request = ServiceRequest::create([
            'tracking_reference' => 'REQ-2026-0003',
            'user_id' => $this->staffA->id,
            'unit' => 'registry',
            'category' => 'general_registry',
            'subject' => 'Test Subject',
            'description' => 'Initial query',
            'priority' => 'standard',
            'status' => 'open',
        ]);

        // Applicant submits with is_internal = 1
        $this->actingAs($this->staffA)->post(route('service-requests.reply', $request), [
            'message' => 'Applicant follow-up attempt to set internal.',
            'is_internal' => 1,
        ]);

        $message = ServiceRequestMessage::where('service_request_id', $request->id)
            ->where('message', 'Applicant follow-up attempt to set internal.')
            ->first();

        $this->assertNotNull($message);
        $this->assertFalse($message->is_internal); // Forced to false for non-handlers
    }

    public function test_unit_handler_can_assign_officer_and_update_status_with_audit_trail(): void
    {
        $request = ServiceRequest::create([
            'tracking_reference' => 'REQ-2026-0004',
            'user_id' => $this->staffA->id,
            'unit' => 'registry',
            'category' => 'service_record',
            'subject' => 'Service Record Attestation',
            'description' => 'Please provide attestation.',
            'priority' => 'standard',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->hrAdmin)->post(route('unit.service-requests.update-status', $request), [
            'status' => 'in_progress',
            'assigned_to_id' => $this->hrAdmin->id,
            'comment' => 'Assigned to self for review and drafting.',
        ]);

        $response->assertRedirect();
        $request->refresh();
        $this->assertEquals(ServiceRequest::STATUS_IN_PROGRESS, $request->status);
        $this->assertEquals($this->hrAdmin->id, $request->assigned_to_id);
        $this->assertNotNull($request->assigned_at);

        $this->assertDatabaseHas('service_request_status_logs', [
            'service_request_id' => $request->id,
            'actor_id' => $this->hrAdmin->id,
            'from_status' => 'open',
            'to_status' => 'in_progress',
            'comment' => 'Assigned to self for review and drafting.',
        ]);
    }

    public function test_applicant_replying_to_resolved_request_reopens_it(): void
    {
        $request = ServiceRequest::create([
            'tracking_reference' => 'REQ-2026-0005',
            'user_id' => $this->staffA->id,
            'unit' => 'registry',
            'category' => 'general_registry',
            'subject' => 'Reopen Test',
            'description' => 'Initial query',
            'priority' => 'standard',
            'status' => ServiceRequest::STATUS_RESOLVED,
            'resolved_at' => now()->subDay(),
        ]);

        $this->actingAs($this->staffA)->post(route('service-requests.reply', $request), [
            'message' => 'I still need clarification on paragraph 2.',
        ]);

        $request->refresh();
        $this->assertEquals(ServiceRequest::STATUS_OPEN, $request->status);

        $this->assertDatabaseHas('service_request_status_logs', [
            'service_request_id' => $request->id,
            'actor_id' => $this->staffA->id,
            'from_status' => ServiceRequest::STATUS_RESOLVED,
            'to_status' => ServiceRequest::STATUS_OPEN,
        ]);
    }

    public function test_authorized_officers_can_view_reports_dashboard_scoped_to_their_role(): void
    {
        // 1. HR Admin visits reports
        $hrResponse = $this->actingAs($this->hrAdmin)->get(route('reports.index'));
        $hrResponse->assertOk();
        $hrResponse->assertSee('Operational Reports');
        $hrResponse->assertSee('TOTAL REGISTERED STAFF');

        // 2. Bursary Admin visits reports
        $bursaryResponse = $this->actingAs($this->bursaryAdmin)->get(route('reports.index'));
        $bursaryResponse->assertOk();
        $bursaryResponse->assertSee('BURSARY PAYSLIP BATCHES');

        // 3. Regular Staff is denied
        $staffResponse = $this->actingAs($this->staffA)->get(route('reports.index'));
        $staffResponse->assertForbidden();
    }

    public function test_authorized_officer_can_export_report_and_it_is_logged_to_audit_registry(): void
    {
        $response = $this->actingAs($this->hrAdmin)->get(route('reports.export', [
            'report_type' => 'personnel_summary',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Verify CSV content
        $content = $response->streamedContent();
        $this->assertStringContainsString('Staff ID', $content);
        $this->assertStringContainsString('Staff Alice', $content);

        // Verify audit log entry
        $this->assertDatabaseHas('report_export_audits', [
            'actor_id' => $this->hrAdmin->id,
            'report_type' => 'personnel_summary',
            'format' => 'csv',
        ]);
    }

    public function test_unauthorized_report_export_attempt_is_rejected(): void
    {
        // HR Admin attempts to export Bursary payroll reconciliation
        $response = $this->actingAs($this->hrAdmin)->get(route('reports.export', [
            'report_type' => 'payslip_reconciliation',
        ]));

        $response->assertForbidden();
        $this->assertDatabaseEmpty('report_export_audits');
    }
}
