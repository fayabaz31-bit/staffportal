<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Append-only audit trail and permission-scoped read-only viewer
 * (PRD §4.9, AUTH F7, AC-10, AC-15).
 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_entries_are_append_only(): void
    {
        $entry = AuditLog::record('memo.published', null, ['recipients' => 3]);

        $this->assertDatabaseHas('audit_logs', ['id' => $entry->id, 'action' => 'memo.published']);
        $this->assertNull($entry->updated_at);
        $this->assertArrayHasKey('recipients', $entry->context);

        $entry->action = 'memo.tampered';

        try {
            $entry->save();
            $this->fail('An existing audit entry was updated; it must be immutable.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        try {
            $entry->delete();
            $this->fail('An audit entry was deleted; the trail must be append-only.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }

        $this->assertDatabaseHas('audit_logs', ['id' => $entry->id, 'action' => 'memo.published']);
    }

    #[DataProvider('authorisedViewers')]
    public function test_authorised_roles_can_review_the_audit_trail(UserRole $role): void
    {
        $viewer = User::factory()->create([
            'name' => 'Audit Viewer',
            'role' => $role,
            'status' => 'active',
        ]);
        AuditLog::record('account.role_changed', $viewer, ['new_role' => 'director'], $viewer->id);

        $this->actingAs($viewer)
            ->get(route('audit.index'))
            ->assertOk()
            ->assertSeeText('account.role_changed')
            ->assertSeeText('Audit Viewer');
    }

    #[DataProvider('forbiddenViewers')]
    public function test_unauthorised_roles_cannot_review_the_audit_trail(UserRole $role): void
    {
        $viewer = User::factory()->create(['role' => $role, 'status' => 'active']);

        $this->actingAs($viewer)
            ->get(route('audit.index'))
            ->assertForbidden();
    }

    public function test_audit_trail_is_not_available_to_guests(): void
    {
        $this->get(route('audit.index'))->assertRedirect(route('login'));
    }

    public function test_the_viewer_filters_entries_by_action(): void
    {
        $ict = User::factory()->create(['role' => UserRole::IctSuperadmin, 'status' => 'active']);
        AuditLog::record('account.role_changed', null, [], $ict->id);
        AuditLog::record('memo.published', null, ['recipients' => 1], $ict->id);

        $this->actingAs($ict)
            ->get(route('audit.index', ['action' => 'account.role_changed']))
            ->assertOk()
            ->assertSeeText('account.role_changed')
            ->assertDontSeeText('memo.published');
    }

    /** @return array<string, array{UserRole}> */
    public static function authorisedViewers(): array
    {
        return [
            'ICT superadmin' => [UserRole::IctSuperadmin],
            'Institutional superadmin' => [UserRole::InstitutionalSuperadmin],
            'Auditor read-only' => [UserRole::AuditorReadonly],
        ];
    }

    /** @return array<string, array{UserRole}> */
    public static function forbiddenViewers(): array
    {
        return [
            'Staff' => [UserRole::Staff],
            'HR / Registry admin' => [UserRole::HrRegistryAdmin],
            'Bursary admin' => [UserRole::BursaryAdmin],
            'Head of department' => [UserRole::HeadOfDepartment],
            'Director' => [UserRole::Director],
        ];
    }
}
