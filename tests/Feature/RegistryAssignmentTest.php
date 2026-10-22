<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\Department;
use App\Models\ReportingRelationship;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkAssignment;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Duty assignment: a piece of work with a validated lifecycle that is separate
 * from the permission catalogue and from document approval.
 */
class RegistryAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supervisor_can_assign_a_duty_to_a_report_and_the_officer_can_complete_the_cycle(): void
    {
        Storage::fake('private');

        [$supervisor, $officer] = $this->registrySupervisorAndOfficer();

        $this->actingAs($supervisor)
            ->post(route('assignments.store'), [
                'title' => 'Update personnel file',
                'instructions' => 'Bring the officer file up to date.',
                'assigned_to' => $officer->id,
                'priority' => 'high',
                'confidentiality' => 'internal',
                'due_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'required_deliverables' => 'A completed file checklist.',
            ])
            ->assertRedirect();

        $assignment = WorkAssignment::query()->sole();

        $this->assertStringStartsWith('NFLV/REG/DUTY/', $assignment->reference_number);
        $this->assertSame($officer->id, $assignment->assigned_to);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $officer->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'assignment.created', 'actor_id' => $supervisor->id]);

        $this->actingAs($officer)->get(route('assignments.show', $assignment))->assertOk();

        foreach (['acknowledge', 'start', 'submit'] as $action) {
            $this->actingAs($officer)
                ->post(route('assignments.transition', $assignment), ['action' => $action])
                ->assertRedirect();
        }

        $this->assertSame('submitted', $assignment->fresh()->status);

        $this->actingAs($supervisor)
            ->post(route('assignments.transition', $assignment), ['action' => 'complete'])
            ->assertRedirect();

        $this->assertSame('completed', $assignment->fresh()->status);
        $this->assertDatabaseCount('assignment_events', 5);
    }

    public function test_an_assignee_cannot_close_their_own_duty(): void
    {
        [$supervisor, $officer] = $this->registrySupervisorAndOfficer();
        $assignment = $this->assign($supervisor, $officer);

        $this->actingAs($officer)
            ->post(route('assignments.transition', $assignment), ['action' => 'complete'])
            ->assertForbidden();

        $this->assertNotSame('completed', $assignment->fresh()->status);
    }

    public function test_a_return_requires_a_comment_and_creates_a_new_version_for_resubmission(): void
    {
        [$supervisor, $officer] = $this->registrySupervisorAndOfficer();
        $assignment = $this->assign($supervisor, $officer);

        $this->actingAs($officer)->post(route('assignments.transition', $assignment), ['action' => 'acknowledge']);
        $this->actingAs($officer)->post(route('assignments.transition', $assignment), ['action' => 'submit']);

        $this->actingAs($supervisor)
            ->post(route('assignments.transition', $assignment), ['action' => 'return'])
            ->assertSessionHasErrors('comments');

        $this->actingAs($supervisor)
            ->post(route('assignments.transition', $assignment), [
                'action' => 'return',
                'comments' => 'Attach the signed checklist.',
            ])
            ->assertRedirect();

        $this->assertSame('returned', $assignment->fresh()->status);
        $this->assertDatabaseHas('assignment_comments', [
            'work_assignment_id' => $assignment->id,
            'kind' => 'correction',
        ]);

        $this->actingAs($officer)
            ->post(route('assignments.transition', $assignment), ['action' => 'submit'])
            ->assertRedirect();

        $this->assertSame('resubmitted', $assignment->fresh()->status);
    }

    public function test_unrelated_officers_cannot_view_or_act_on_a_duty(): void
    {
        [$supervisor, $officer] = $this->registrySupervisorAndOfficer();
        $assignment = $this->assign($supervisor, $officer);

        $outsider = User::factory()->create(['role' => UserRole::BursaryAdmin]);

        $this->actingAs($outsider)->get(route('assignments.show', $assignment))->assertForbidden();
        $this->actingAs($outsider)
            ->post(route('assignments.transition', $assignment), ['action' => 'acknowledge'])
            ->assertForbidden();
    }

    public function test_an_officer_cannot_assign_work_to_someone_they_do_not_supervise(): void
    {
        [$supervisor, $officer] = $this->registrySupervisorAndOfficer();
        $stranger = User::factory()->create(['role' => UserRole::Staff]);
        StaffProfile::factory()->create([
            'user_id' => $stranger->id,
            'department_id' => Department::factory()->create()->id,
        ]);

        $this->actingAs($supervisor)
            ->post(route('assignments.store'), [
                'title' => 'Unrelated work',
                'instructions' => 'Should not be allowed.',
                'assigned_to' => $stranger->id,
                'priority' => 'normal',
                'confidentiality' => 'internal',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('work_assignments', 0);
    }

    public function test_attachments_are_private_and_downloads_are_audited(): void
    {
        Storage::fake('private');

        [$supervisor, $officer] = $this->registrySupervisorAndOfficer();
        $assignment = $this->assign($supervisor, $officer);

        $this->actingAs($officer)
            ->post(route('assignments.attachments.store', $assignment), [
                'file' => UploadedFile::fake()->create('evidence.pdf', 30, 'application/pdf'),
            ])
            ->assertRedirect();

        $attachment = $assignment->attachments()->sole();
        Storage::disk('private')->assertExists($attachment->storage_path);

        $this->actingAs($officer)
            ->get(route('assignments.attachments.download', [$assignment, $attachment]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assignment.attachment_downloaded',
            'subject_id' => $assignment->id,
        ]);
    }

    public function test_a_configured_reporting_line_lets_a_director_assign_without_a_registry_duty(): void
    {
        [$supervisor] = $this->registrySupervisorAndOfficer();
        $director = User::factory()->create(['role' => UserRole::Director]);
        StaffProfile::factory()->create([
            'user_id' => $director->id,
            'department_id' => Department::factory()->create(['code' => 'DIRECTORATE', 'name' => 'Directorate Office'])->id,
        ]);

        ReportingRelationship::query()->create([
            'subordinate_id' => $supervisor->id,
            'supervisor_id' => $director->id,
            'relationship_type' => ReportingRelationship::LINE_MANAGER,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_primary' => true,
            'created_by' => $director->id,
        ]);

        $this->assertTrue($director->fresh()->isSupervisorOf($supervisor));

        $this->actingAs($director)
            ->post(route('assignments.store'), [
                'title' => 'Executive briefing',
                'instructions' => 'Prepare the briefing pack.',
                'assigned_to' => $supervisor->id,
                'priority' => 'normal',
                'confidentiality' => 'confidential',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('work_assignments', 1);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function registrySupervisorAndOfficer(): array
    {
        $department = Department::factory()->create(['code' => 'REGISTRY', 'name' => 'Registry Department']);

        $supervisor = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        StaffProfile::factory()->create(['user_id' => $supervisor->id, 'department_id' => $department->id]);
        $this->grant($supervisor, DutyCatalog::REGISTRY_HEAD_REVIEW);

        $officer = User::factory()->create(['role' => UserRole::Staff]);
        StaffProfile::factory()->create(['user_id' => $officer->id, 'department_id' => $department->id]);

        return [$supervisor->fresh(), $officer->fresh()];
    }

    private function assign(User $supervisor, User $officer): WorkAssignment
    {
        $this->actingAs($supervisor)
            ->post(route('assignments.store'), [
                'title' => 'Review personnel file',
                'instructions' => 'Check the file against the checklist.',
                'assigned_to' => $officer->id,
                'priority' => 'normal',
                'confidentiality' => 'internal',
            ])
            ->assertRedirect();

        return WorkAssignment::query()->latest('id')->firstOrFail();
    }

    private function grant(User $user, string $code): void
    {
        $duty = DutyCatalog::ensureDuty($code);

        if (! $user->duties()->whereKey($duty->getKey())->exists()) {
            $user->duties()->attach($duty->getKey(), ['assigned_at' => now()]);
        }

        $user->unsetRelation('duties');
    }
}
