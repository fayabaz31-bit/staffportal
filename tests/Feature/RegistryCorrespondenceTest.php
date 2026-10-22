<?php

namespace Tests\Feature;

use App\DutyCatalog;
use App\Models\CorrespondenceCategory;
use App\Models\CorrespondenceDocument;
use App\Models\Department;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Support\RegistryWorkflowCatalog;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Registry correspondence: versioned drafts, a pinned approval workflow, and a
 * separate issuance step. Approval never implies delivery.
 */
class RegistryCorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RegistryWorkflowCatalog::sync();
    }

    public function test_a_routine_document_moves_from_draft_through_head_of_hr_and_registrar_to_issue(): void
    {
        Storage::fake('private');

        [$originator, $headOfHr, $registrar] = $this->registryChain();

        $category = CorrespondenceCategory::query()->where('code', 'annual_increment')->firstOrFail();

        $this->actingAs($originator)
            ->post(route('registry.correspondence.store'), [
                'correspondence_category_id' => $category->id,
                'recipient_user_id' => $originator->id,
                'subject' => 'Annual increment for 2026',
                'body' => '<p>Your increment has been approved.</p>',
            ])
            ->assertRedirect();

        $document = CorrespondenceDocument::query()->sole();

        $this->assertSame(CorrespondenceDocument::STATUS_DRAFT, $document->status);
        $this->assertSame(1, $document->current_version);

        $this->actingAs($originator)
            ->post(route('registry.correspondence.submit', $document))
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(CorrespondenceDocument::STATUS_UNDER_REVIEW, $document->status);
        $instance = WorkflowInstance::query()->sole();
        $this->assertNotNull($instance->workflow_version_id);
        $this->assertSame('head_of_hr_recommend', $instance->currentStep->code);

        // The originator cannot approve their own document.
        $this->actingAs($originator)
            ->post(route('registry.correspondence.decide', $document), ['action' => 'approve'])
            ->assertSessionHasErrors('workflow');

        $this->actingAs($headOfHr)
            ->post(route('registry.correspondence.decide', $document), [
                'action' => 'recommend',
                'comments' => 'Checked against the personnel record.',
            ])
            ->assertRedirect();

        $this->assertSame('registrar_approve', $document->fresh()->workflowInstance->currentStep->code);

        $this->actingAs($registrar)
            ->post(route('registry.correspondence.decide', $document), ['action' => 'approve'])
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(CorrespondenceDocument::STATUS_APPROVED, $document->status);
        $this->assertNotNull($document->approved_at);
        $this->assertNull($document->issued_at);

        $this->actingAs($registrar)
            ->post(route('registry.correspondence.issue', $document), ['delivery_status' => 'collected'])
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(CorrespondenceDocument::STATUS_ISSUED, $document->status);
        $this->assertSame('collected', $document->delivery_status);
        $this->assertNotNull($document->storage_path);
        Storage::disk('private')->assertExists($document->storage_path);

        // Issued documents are immutable.
        $this->actingAs($originator)
            ->patch(route('registry.correspondence.revise', $document), [
                'subject' => 'Changed after issue',
                'body' => '<p>Changed.</p>',
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_a_new_edit_creates_an_immutable_version(): void
    {
        [$originator] = $this->registryChain();
        $document = $this->draft($originator, 'annual_increment');

        $this->actingAs($originator)
            ->patch(route('registry.correspondence.revise', $document), [
                'subject' => 'Corrected subject',
                'body' => '<p>Corrected body.</p>',
            ])
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(2, $document->current_version);
        $this->assertSame(2, $document->versions()->count());
        $this->assertSame('<p>Corrected body.</p>', $document->latestVersion()?->body_html);
    }

    public function test_promotion_correspondence_cannot_be_issued_before_the_director_decides(): void
    {
        [$originator, $headOfHr, $registrar, $director] = $this->registryChain(withDirector: true);

        $document = $this->draft($originator, 'promotion');
        $this->actingAs($originator)->post(route('registry.correspondence.submit', $document));

        $this->actingAs($headOfHr)
            ->post(route('registry.correspondence.decide', $document), ['action' => 'review']);

        $this->actingAs($registrar)
            ->post(route('registry.correspondence.decide', $document), [
                'action' => 'recommend',
                'comments' => 'Recommended for executive approval.',
            ]);

        $this->assertSame('director_approve', $document->fresh()->workflowInstance->currentStep->code);

        // Still awaiting the executive decision, so issuance is refused.
        $this->actingAs($registrar)
            ->post(route('registry.correspondence.issue', $document), ['delivery_status' => 'not_sent'])
            ->assertSessionHasErrors('document');

        $this->actingAs($registrar)
            ->post(route('registry.correspondence.decide', $document), ['action' => 'approve'])
            ->assertForbidden();

        $this->actingAs($director)
            ->post(route('registry.correspondence.decide', $document), ['action' => 'approve'])
            ->assertRedirect();

        $this->assertSame(CorrespondenceDocument::STATUS_APPROVED, $document->fresh()->status);
    }

    public function test_an_officer_outside_the_stage_cannot_view_or_decide_confidential_correspondence(): void
    {
        [$originator, $headOfHr] = $this->registryChain();
        $document = $this->draft($originator, 'annual_increment');

        $trainee = User::factory()->create(['role' => UserRole::Staff]);
        StaffProfile::factory()->create([
            'user_id' => $trainee->id,
            'department_id' => Department::factory()->create()->id,
        ]);

        $this->actingAs($originator)->post(route('registry.correspondence.submit', $document));

        $this->actingAs($trainee)->get(route('registry.correspondence.show', $document))->assertForbidden();
        $this->actingAs($trainee)
            ->post(route('registry.correspondence.decide', $document), ['action' => 'approve'])
            ->assertForbidden();

        // The Head of HR can see it because they hold the first-stage duty.
        $this->actingAs($headOfHr)->get(route('registry.correspondence.show', $document))->assertOk();
    }

    public function test_a_returned_document_can_be_corrected_and_resubmitted_into_the_same_workflow_version(): void
    {
        [$originator, $headOfHr] = $this->registryChain();
        $document = $this->draft($originator, 'annual_increment');
        $this->actingAs($originator)->post(route('registry.correspondence.submit', $document));

        $versionId = $document->fresh()->workflowInstance->workflow_version_id;

        $this->actingAs($headOfHr)
            ->post(route('registry.correspondence.decide', $document), [
                'action' => 'return',
                'comments' => 'Confirm the effective date.',
            ])
            ->assertRedirect();

        $this->assertSame(CorrespondenceDocument::STATUS_RETURNED, $document->fresh()->status);

        $this->actingAs($originator)->patch(route('registry.correspondence.revise', $document), [
            'subject' => $document->subject,
            'body' => '<p>Corrected with the effective date.</p>',
        ])->assertRedirect();

        $this->actingAs($originator)
            ->post(route('registry.correspondence.submit', $document))
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(CorrespondenceDocument::STATUS_SUBMITTED, $document->status);
        $this->assertSame($versionId, $document->workflowInstance->workflow_version_id);
        $this->assertSame(2, $document->current_version);
    }

    public function test_attachments_are_stored_privately_audited_and_idor_protected(): void
    {
        Storage::fake('private');

        [$originator] = $this->registryChain();
        $document = $this->draft($originator, 'annual_increment');

        $this->actingAs($originator)
            ->post(route('registry.correspondence.attachments.store', $document), [
                'file' => UploadedFile::fake()->create('evidence.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect();

        $attachment = $document->attachments()->sole();
        Storage::disk('private')->assertExists($attachment->storage_path);

        $this->actingAs($originator)
            ->get(route('registry.correspondence.attachments.download', [$document, $attachment]))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'correspondence.attachment_downloaded',
            'subject_id' => $document->id,
        ]);

        $trainee = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($trainee)
            ->get(route('registry.correspondence.attachments.download', [$document, $attachment]))
            ->assertForbidden();

        // An attachment cannot be pulled through a different document's URL.
        $otherDocument = $this->draft($originator, 'annual_increment');

        $this->actingAs($originator)
            ->get(route('registry.correspondence.attachments.download', [$otherDocument, $attachment]))
            ->assertNotFound();
    }

    public function test_an_ict_technical_administrator_cannot_reach_personnel_correspondence(): void
    {
        [$originator] = $this->registryChain();
        $document = $this->draft($originator, 'annual_increment');

        $ict = User::factory()->create(['role' => UserRole::IctSuperadmin]);

        $this->actingAs($ict)->get(route('registry.correspondence.show', $document))->assertForbidden();
        $this->actingAs($ict)->get(route('registry.correspondence.download', $document))->assertForbidden();
        $this->actingAs($ict)->get(route('registry.correspondence.index'))->assertOk();
    }

    public function test_only_the_originator_can_add_an_attachment(): void
    {
        Storage::fake('private');

        [$originator, $headOfHr] = $this->registryChain();
        $document = $this->draft($originator, 'annual_increment');

        $this->actingAs($headOfHr)
            ->post(route('registry.correspondence.attachments.store', $document), [
                'file' => UploadedFile::fake()->create('note.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('document_attachments', 0);
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3?: User}
     */
    private function registryChain(bool $withDirector = false): array
    {
        $department = Department::factory()->create(['code' => 'REGISTRY', 'name' => 'Registry Department']);

        $make = function (UserRole $role, string $name) use ($department): User {
            $user = User::factory()->create(['role' => $role, 'name' => $name]);
            StaffProfile::factory()->create(['user_id' => $user->id, 'department_id' => $department->id]);

            return $user->fresh();
        };

        $originator = $make(UserRole::HrRegistryAdmin, 'Registry Officer');
        $headOfHr = $make(UserRole::HrRegistryAdmin, 'Head of HR');
        $registrar = $make(UserRole::HrRegistryAdmin, 'Registrar');

        $this->grant($headOfHr, DutyCatalog::REGISTRY_HEAD_REVIEW);
        $this->grant($registrar, DutyCatalog::REGISTRY_REGISTRAR_APPROVE);

        if (! $withDirector) {
            return [$originator, $headOfHr, $registrar];
        }

        $director = $make(UserRole::Director, 'Director');
        $this->grant($director, DutyCatalog::REGISTRY_DIRECTOR_ESCALATION);

        return [$originator, $headOfHr, $registrar, $director];
    }

    private function draft(User $originator, string $categoryCode): CorrespondenceDocument
    {
        $category = CorrespondenceCategory::query()->where('code', $categoryCode)->firstOrFail();

        $this->actingAs($originator)
            ->post(route('registry.correspondence.store'), [
                'correspondence_category_id' => $category->id,
                'recipient_user_id' => $originator->id,
                'subject' => 'Draft correspondence',
                'body' => '<p>Draft body.</p>',
            ])
            ->assertRedirect();

        return CorrespondenceDocument::query()->latest('id')->firstOrFail();
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
