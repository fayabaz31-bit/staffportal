<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use App\Models\StaffProfile;
use App\Models\User;
use App\Notifications\PortalNotifier;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * Account lifecycle, immediate role reassignment and portal notifications
 * (PRD §2.1/§4.8/§E, AUTH F1, F7, AC-10, AC-11, AC-15).
 */
class AccountLifecycleAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $ictAdmin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ictAdmin = User::factory()->create([
            'role' => UserRole::IctSuperadmin,
            'status' => 'active',
        ]);

        $this->staff = User::factory()->create([
            'staff_number' => 'NFLV-7001',
            'role' => UserRole::Staff,
            'status' => 'active',
            'password' => bcrypt('password123'),
        ]);

        StaffProfile::factory()->create(['user_id' => $this->staff->id]);
    }

    /**
     * AC-15 / F1: Deactivation blocks authentication while retained history stays intact.
     */
    public function test_ict_can_deactivate_an_account_and_retain_historical_records(): void
    {
        $leave = LeaveApplication::factory()->create([
            'user_id' => $this->staff->id,
            'status' => LeaveApplication::STATUS_APPROVED,
        ]);

        $this->actingAs($this->ictAdmin)
            ->post(route('ict.accounts.status', $this->staff), [
                'status' => 'deactivated',
                'reason' => 'Employment ended; portal access withdrawn.',
            ])
            ->assertRedirect();

        $this->assertSame('deactivated', $this->staff->fresh()->status);

        $this->signOutTestAdministrator();

        // Authentication is blocked for the deactivated account.
        $this->post(route('login.store'), [
            'identifier' => 'NFLV-7001',
            'password' => 'password123',
        ])->assertSessionHasErrors('identifier');
        $this->assertGuest();

        // Historical records are retained, not deleted.
        $this->assertDatabaseHas('leave_applications', [
            'id' => $leave->id,
            'user_id' => $this->staff->id,
            'status' => LeaveApplication::STATUS_APPROVED,
        ]);

        // The privileged change is recorded in the append-only audit trail.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.status_changed',
            'actor_id' => $this->ictAdmin->id,
            'subject_id' => $this->staff->id,
        ]);
    }

    /**
     * F1: Reactivation restores sign-in access without recreating the account.
     */
    public function test_reactivating_an_account_restores_sign_in_and_notifies_the_staff_member(): void
    {
        $this->staff->update(['status' => 'suspended']);

        $this->actingAs($this->ictAdmin)
            ->post(route('ict.accounts.status', $this->staff), ['status' => 'active'])
            ->assertRedirect();

        $this->assertSame('active', $this->staff->fresh()->status);

        $this->signOutTestAdministrator();

        $this->post(route('login.store'), [
            'identifier' => 'NFLV-7001',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $notification = $this->staff->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Account activated', $notification->data['title']);
    }

    public function test_an_administrator_cannot_suspend_or_deactivate_their_own_account(): void
    {
        $this->actingAs($this->ictAdmin)
            ->from(route('ict.accounts.index'))
            ->post(route('ict.accounts.status', $this->ictAdmin), ['status' => 'deactivated'])
            ->assertRedirect(route('ict.accounts.index'))
            ->assertSessionHas('error');

        $this->assertSame('active', $this->ictAdmin->fresh()->status);
    }

    /**
     * AC-10: A role change is effective immediately and is recorded.
     */
    public function test_role_change_takes_immediate_effect_and_is_audited(): void
    {
        $this->actingAs($this->staff)->get(route('reports.index'))->assertForbidden();

        $this->actingAs($this->ictAdmin)
            ->post(route('ict.accounts.role', $this->staff), ['role' => UserRole::Director->value])
            ->assertRedirect();

        $this->assertSame(UserRole::Director, $this->staff->fresh()->role);

        // No re-login required: the very next permission check reflects the change.
        $this->actingAs($this->staff->fresh())->get(route('reports.index'))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.role_changed',
            'actor_id' => $this->ictAdmin->id,
            'subject_id' => $this->staff->id,
        ]);

        $notification = $this->staff->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Role assignment updated', $notification->data['title']);
    }

    public function test_only_an_institutional_superadmin_may_assign_the_institutional_superadmin_role(): void
    {
        $this->actingAs($this->ictAdmin)
            ->from(route('ict.accounts.index'))
            ->post(route('ict.accounts.role', $this->staff), ['role' => UserRole::InstitutionalSuperadmin->value])
            ->assertRedirect(route('ict.accounts.index'))
            ->assertSessionHas('error');

        $this->assertSame(UserRole::Staff, $this->staff->fresh()->role);

        $institutional = User::factory()->create([
            'role' => UserRole::InstitutionalSuperadmin,
            'status' => 'active',
        ]);

        $this->actingAs($institutional)
            ->post(route('ict.accounts.role', $this->staff), ['role' => UserRole::InstitutionalSuperadmin->value])
            ->assertRedirect();

        $this->assertSame(UserRole::InstitutionalSuperadmin, $this->staff->fresh()->role);
    }

    public function test_temporary_password_is_issued_once_and_never_stored_in_plain_text(): void
    {
        $previousHash = $this->staff->password;

        $response = $this->actingAs($this->ictAdmin)
            ->post(route('ict.accounts.password', $this->staff));

        $response->assertRedirect()->assertSessionHas('temporaryPassword');

        $temporaryPassword = session('temporaryPassword');
        $this->assertIsString($temporaryPassword);
        $this->assertNotSame($previousHash, $this->staff->fresh()->password);
        $this->assertNotSame($temporaryPassword, $this->staff->fresh()->password);
        $this->assertTrue(Hash::check($temporaryPassword, $this->staff->fresh()->password));
        $this->assertDatabaseMissing('users', ['password' => $temporaryPassword]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.temporary_password_issued',
            'actor_id' => $this->ictAdmin->id,
            'subject_id' => $this->staff->id,
        ]);
    }

    public function test_account_operations_console_is_restricted_to_authorised_roles(): void
    {
        $institutional = User::factory()->create([
            'role' => UserRole::InstitutionalSuperadmin,
            'status' => 'active',
        ]);
        $hrAdmin = User::factory()->create([
            'role' => UserRole::HrRegistryAdmin,
            'status' => 'active',
        ]);
        $auditor = User::factory()->create([
            'role' => UserRole::AuditorReadonly,
            'status' => 'active',
        ]);

        $this->actingAs($this->ictAdmin)->get(route('ict.accounts.index'))->assertOk();
        $this->actingAs($institutional)->get(route('ict.accounts.index'))->assertOk();
        $this->actingAs($hrAdmin)->get(route('ict.accounts.index'))->assertForbidden();
        $this->actingAs($auditor)->get(route('ict.accounts.index'))->assertForbidden();
        $this->actingAs($this->staff)->get(route('ict.accounts.index'))->assertForbidden();
    }

    /**
     * F7 / AC-11: The in-app inbox record is written immediately and can be read.
     */
    public function test_in_app_notification_is_delivered_immediately_and_can_be_marked_read(): void
    {
        PortalNotifier::send(
            $this->staff,
            'Leave application approved',
            'Your leave request has been approved.',
            route('leave.index'),
            'leave',
        );

        $this->assertDatabaseCount('notifications', 1);

        $this->actingAs($this->staff)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSeeText('Leave application approved');

        $notification = $this->staff->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);

        $this->actingAs($this->staff)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('leave.index'));

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($this->staff)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $this->staff->fresh()->unreadNotifications()->count());
    }

    /**
     * Open-redirect defence: an untrusted destination in a notification must not
     * send the staff member off-site.
     */
    #[DataProvider('externalNotificationDestinations')]
    public function test_notification_deep_link_to_an_external_destination_falls_back_to_the_inbox(string $url): void
    {
        PortalNotifier::send(
            $this->staff,
            'Suspicious notice',
            'Do not trust this destination.',
            $url,
            'account',
        );

        $notification = $this->staff->notifications()->firstOrFail();

        $this->actingAs($this->staff)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'));
    }

    /** @return array<string, array{string}> */
    public static function externalNotificationDestinations(): array
    {
        return [
            'absolute foreign origin' => ['https://evil.example/phish'],
            'protocol-relative' => ['//evil.example/phish'],
            'javascript scheme' => ['javascript:alert(1)'],
        ];
    }

    public function test_a_staff_member_cannot_open_another_members_notification(): void
    {
        PortalNotifier::send(
            $this->staff,
            'Private payslip notice',
            'A new payslip is available.',
            route('payslips.index'),
            'payslip',
        );

        $other = User::factory()->create(['role' => UserRole::Staff, 'status' => 'active']);
        $notification = $this->staff->notifications()->firstOrFail();

        $this->actingAs($other)
            ->post(route('notifications.read', $notification))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    /**
     * AC-11: A mail delivery failure must never erase the authoritative record.
     */
    public function test_email_delivery_failure_retains_the_in_app_notification(): void
    {
        Mail::extend('throwing', function (): TransportInterface {
            return new class implements TransportInterface
            {
                public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
                {
                    throw new RuntimeException('SMTP transport is unavailable.');
                }

                public function __toString(): string
                {
                    return 'throwing';
                }
            };
        });

        config([
            'mail.default' => 'throwing',
            'mail.mailers.throwing' => ['transport' => 'throwing'],
        ]);

        Log::spy();

        PortalNotifier::send(
            $this->staff,
            'Payslip published',
            'A new payslip is available in your portal.',
            route('payslips.index'),
            'payslip',
        );

        // The in-app record survives despite the email transport failing.
        $this->assertSame(1, $this->staff->fresh()->notifications()->count());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->staff->id,
        ]);

        Log::shouldHaveReceived('warning')->once();
    }

    /**
     * Clear the authenticated test guard so a subsequent login request is
     * actually evaluated instead of being short-circuited by the guest middleware.
     */
    private function signOutTestAdministrator(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
