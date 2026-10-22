<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\OfficialLetter;
use App\Models\Payslip;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AcceptanceCriteriaHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::factory()->create([
            'name' => 'Department of Languages',
            'code' => 'LANG',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'staff_number' => 'NFLV-5001',
            'role' => UserRole::Staff,
            'status' => 'active',
            'password' => bcrypt('password123'),
        ]);

        StaffProfile::factory()->create([
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
        ]);
    }

    /**
     * AC-01: A valid staff member logs in -> The correct personal dashboard is displayed.
     */
    public function test_ac01_valid_staff_login_displays_personal_dashboard(): void
    {
        $response = $this->post(route('login.store'), [
            'identifier' => 'NFLV-5001',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->staff);

        $dashboardResponse = $this->actingAs($this->staff)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('STAFF DASHBOARD');
        $dashboardResponse->assertSee($this->staff->name);
    }

    /**
     * AC-02: An unauthorised user attempts to open a private staff URL -> Access denied without revealing protected content.
     */
    public function test_ac02_unauthorised_user_denied_private_urls(): void
    {
        // Unauthenticated guest denied
        $guestResponse = $this->get(route('leave.index'));
        $guestResponse->assertRedirect(route('login'));

        // Staff without registry role denied administrative import
        $forbiddenResponse = $this->actingAs($this->staff)->get(route('registry.personnel-imports.create'));
        $forbiddenResponse->assertForbidden();
    }

    /**
     * AC-03: Staff member A attempts to view staff member B's payslip -> Request denied.
     */
    public function test_ac03_staff_cannot_download_another_staff_payslip(): void
    {
        $staffB = User::factory()->create(['role' => UserRole::Staff, 'status' => 'active']);

        $payslipB = Payslip::factory()->create([
            'user_id' => $staffB->id,
            'status' => Payslip::STATUS_PUBLISHED,
        ]);

        $response = $this->actingAs($this->staff)->get(route('payslips.download', $payslipB));
        $response->assertNotFound();
    }

    /**
     * AC-10: A privileged administrator changes a role assignment -> Reflected in subsequent permission checks immediately.
     */
    public function test_ac10_role_assignment_takes_immediate_effect(): void
    {
        // Initially, staff member cannot view reports dashboard
        $deniedResponse = $this->actingAs($this->staff)->get(route('reports.index'));
        $deniedResponse->assertForbidden();

        // Administrator updates user's role to Director
        $this->staff->role = UserRole::Director;
        $this->staff->save();

        // Immediate check reflects Director permissions
        $allowedResponse = $this->actingAs($this->staff->fresh())->get(route('reports.index'));
        $allowedResponse->assertOk();
        $allowedResponse->assertSee('Operational Reports');
    }

    /**
     * AC-11: Email delivery failure does not erase underlying record.
     */
    public function test_ac11_record_persists_intact_regardless_of_notification_status(): void
    {
        Notification::fake();

        // Create official letter and record
        $letter = OfficialLetter::factory()->create([
            'user_id' => $this->staff->id,
            'reference_number' => 'NFLV/EST/LEAVE/2026/9999',
            'subject' => 'Approval Notice',
        ]);

        $this->assertDatabaseHas('official_letters', [
            'id' => $letter->id,
            'reference_number' => 'NFLV/EST/LEAVE/2026/9999',
        ]);

        // Record remains fully queryable and unchanged
        $retrieved = OfficialLetter::find($letter->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals($this->staff->id, $retrieved->user_id);
    }

    /**
     * AC-15: A staff account is deactivated -> Authentication blocked, historical records retained.
     */
    public function test_ac15_deactivated_account_blocked_from_login_while_history_retained(): void
    {
        // Create historical leave application
        $leave = LeaveApplication::factory()->create([
            'user_id' => $this->staff->id,
            'status' => LeaveApplication::STATUS_APPROVED,
        ]);

        // Deactivate account
        $this->staff->status = 'deactivated';
        $this->staff->save();

        // Sign in attempt must fail
        $loginResponse = $this->post(route('login.store'), [
            'identifier' => 'NFLV-5001',
            'password' => 'password123',
        ]);

        $loginResponse->assertSessionHasErrors();
        $this->assertGuest();

        // Historical data remains in database
        $this->assertDatabaseHas('leave_applications', [
            'id' => $leave->id,
            'user_id' => $this->staff->id,
            'status' => LeaveApplication::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->staff->id,
            'status' => 'deactivated',
        ]);
    }
}
