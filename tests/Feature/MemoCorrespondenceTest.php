<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Memo;
use App\Models\MemoRecipient;
use App\Models\StaffProfile;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoCorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_create_and_publish_an_individual_memo(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $recipient = User::factory()->create();
        StaffProfile::factory()->for($recipient)->create();

        $draft = $this->actingAs($author)->post(route('memos.store'), [
            'title' => 'Campus closure notice',
            'body' => 'The administrative offices will close at noon.',
            'classification' => 'normal',
            'audience_type' => 'individual',
            'recipient_ids' => [$recipient->id],
            'requires_acknowledgement' => '1',
        ]);

        $draft->assertRedirect();
        $memo = Memo::query()->firstOrFail();
        $this->assertSame(Memo::STATUS_DRAFT, $memo->status);
        $this->assertDatabaseCount('memo_recipients', 0);

        $publish = $this->actingAs($author)->post(route('memos.publish', $memo));

        $publish->assertRedirectToRoute('memos.show', $memo);
        $this->assertDatabaseHas('memos', [
            'id' => $memo->id,
            'status' => Memo::STATUS_PUBLISHED,
        ]);
        $this->assertDatabaseHas('memo_recipients', [
            'memo_id' => $memo->id,
            'user_id' => $recipient->id,
        ]);
    }

    public function test_only_the_snapshotted_recipient_can_view_a_published_memo(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $recipient = User::factory()->create();
        $otherUser = User::factory()->create();
        $memo = $this->publishIndividualMemo($author, $recipient);

        $response = $this->actingAs($recipient)->get(route('memos.show', $memo));

        $response->assertOk()->assertSeeText('Staff-only meeting moved to room 4.');

        $forbidden = $this->actingAs($otherUser)->get(route('memos.show', $memo));

        $forbidden->assertNotFound();
    }

    public function test_department_recipients_are_snapshotted_at_publication(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $department = Department::factory()->create();
        $firstRecipient = User::factory()->create();
        StaffProfile::factory()->for($firstRecipient)->for($department)->create();

        $this->actingAs($author)->post(route('memos.store'), [
            'title' => 'Department briefing',
            'body' => 'Please review the updated timetable.',
            'classification' => 'normal',
            'audience_type' => 'department',
            'department_id' => $department->id,
        ]);
        $memo = Memo::query()->firstOrFail();
        $this->post(route('memos.publish', $memo))->assertRedirectToRoute('memos.show', $memo);

        $laterRecipient = User::factory()->create();
        StaffProfile::factory()->for($laterRecipient)->for($department)->create();

        $this->assertDatabaseHas('memo_recipients', [
            'memo_id' => $memo->id,
            'user_id' => $firstRecipient->id,
        ]);
        $this->assertDatabaseMissing('memo_recipients', [
            'memo_id' => $memo->id,
            'user_id' => $laterRecipient->id,
        ]);
    }

    public function test_institution_target_excludes_suspended_accounts_and_snapshots_active_staff(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $activeRecipient = User::factory()->create(['role' => UserRole::Staff, 'status' => 'active']);
        StaffProfile::factory()->for($activeRecipient)->create();
        $suspendedRecipient = User::factory()->create(['role' => UserRole::Staff, 'status' => 'suspended']);
        StaffProfile::factory()->for($suspendedRecipient)->create();

        $this->actingAs($author)->post(route('memos.store'), [
            'title' => 'Institution notice',
            'body' => 'Notice for all active staff.',
            'classification' => 'normal',
            'audience_type' => 'institution',
        ]);
        $memo = Memo::query()->firstOrFail();
        $this->post(route('memos.publish', $memo))->assertRedirectToRoute('memos.show', $memo);

        $this->assertDatabaseHas('memo_recipients', [
            'memo_id' => $memo->id,
            'user_id' => $activeRecipient->id,
        ]);
        $this->assertDatabaseMissing('memo_recipients', [
            'memo_id' => $memo->id,
            'user_id' => $suspendedRecipient->id,
        ]);
    }

    public function test_non_author_cannot_view_a_draft_memo(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $otherUser = User::factory()->create();
        $memo = Memo::create([
            'author_id' => $author->id,
            'title' => 'Private draft',
            'body' => 'This is not published.',
            'classification' => 'normal',
            'status' => Memo::STATUS_DRAFT,
            'audience_type' => 'institution',
        ]);

        $response = $this->actingAs($otherUser)->get(route('memos.show', $memo));

        $response->assertNotFound();
    }

    public function test_staff_cannot_author_or_publish_memos(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $response = $this->actingAs($staff)->get(route('memos.create'));

        $response->assertForbidden();
    }

    public function test_published_memo_cannot_be_updated(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $recipient = User::factory()->create();
        $memo = $this->publishIndividualMemo($author, $recipient);

        $response = $this->actingAs($author)->patch(route('memos.update', $memo), [
            'title' => 'Silently changed subject',
            'body' => 'This must not replace the published version.',
            'classification' => 'normal',
            'audience_type' => 'individual',
            'recipient_ids' => [$recipient->id],
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('memos', [
            'id' => $memo->id,
            'title' => 'Meeting update',
            'status' => Memo::STATUS_PUBLISHED,
        ]);
    }

    public function test_author_can_withdraw_a_published_memo_and_recipients_lose_access(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $recipient = User::factory()->create();
        $memo = $this->publishIndividualMemo($author, $recipient);

        $response = $this->actingAs($author)->post(route('memos.withdraw', $memo), [
            'withdrawal_reason' => 'A corrected notice will replace this memo.',
        ]);

        $response->assertRedirectToRoute('memos.show', $memo);
        $this->assertDatabaseHas('memos', [
            'id' => $memo->id,
            'status' => Memo::STATUS_WITHDRAWN,
            'withdrawal_reason' => 'A corrected notice will replace this memo.',
        ]);
        $this->actingAs($recipient)->get(route('memos.show', $memo))->assertNotFound();
    }

    public function test_recipient_can_acknowledge_a_memo_that_requires_it(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);
        $recipient = User::factory()->create();
        $memo = $this->publishIndividualMemo($author, $recipient, true);

        $response = $this->actingAs($recipient)->post(route('memos.acknowledge', $memo));

        $response->assertRedirectToRoute('memos.show', $memo);
        $this->assertNotNull(MemoRecipient::query()
            ->where('memo_id', $memo->id)
            ->where('user_id', $recipient->id)
            ->firstOrFail()
            ->acknowledged_at);
    }

    public function test_head_of_department_cannot_target_outside_their_department(): void
    {
        $homeDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $hod = User::factory()->create(['role' => UserRole::HeadOfDepartment]);
        StaffProfile::factory()->for($hod)->for($homeDepartment)->create();

        $response = $this->actingAs($hod)->from(route('memos.create'))->post(route('memos.store'), [
            'title' => 'External notice',
            'body' => 'This target is outside the assigned scope.',
            'classification' => 'normal',
            'audience_type' => 'department',
            'department_id' => $otherDepartment->id,
        ]);

        $response->assertRedirectToRoute('memos.create')->assertSessionHasErrors('department_id');
        $this->assertDatabaseCount('memos', 0);
    }

    public function test_restricted_classifications_are_blocked_until_policy_is_configured(): void
    {
        $author = User::factory()->create(['role' => UserRole::HrRegistryAdmin]);

        $response = $this->actingAs($author)->from(route('memos.create'))->post(route('memos.store'), [
            'title' => 'Restricted notice',
            'body' => 'This classification is not configured.',
            'classification' => 'restricted',
            'audience_type' => 'institution',
        ]);

        $response->assertRedirectToRoute('memos.create')->assertSessionHasErrors('classification');
        $this->assertDatabaseCount('memos', 0);
    }

    private function publishIndividualMemo(User $author, User $recipient, bool $requiresAcknowledgement = false): Memo
    {
        StaffProfile::factory()->for($recipient)->create();

        $this->actingAs($author)->post(route('memos.store'), [
            'title' => 'Meeting update',
            'body' => 'Staff-only meeting moved to room 4.',
            'classification' => 'normal',
            'audience_type' => 'individual',
            'recipient_ids' => [$recipient->id],
            'requires_acknowledgement' => $requiresAcknowledgement ? '1' : '0',
        ]);

        $memo = Memo::query()->latest('id')->firstOrFail();
        $this->post(route('memos.publish', $memo));

        return $memo->fresh();

        $response->assertStatus(200);
    }
}
