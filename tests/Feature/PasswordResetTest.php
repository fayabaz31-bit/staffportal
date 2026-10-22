<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Secure self-service password recovery (PRD F1/§J): no account enumeration,
 * single-use tokens and an audited password change.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_request_page_is_available_to_guests(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSeeText('Reset');
    }

    public function test_a_staff_member_can_reset_their_password_with_a_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Staff,
            'status' => 'active',
            'email' => 'reset.me@nfvl.test',
            'staff_number' => 'NFLV-6001',
            'password' => bcrypt('original-password'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-secret-2026',
            'password_confirmation' => 'brand-new-secret-2026',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brand-new-secret-2026', $user->fresh()->password));

        // The password change is recorded and can be used to sign in.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.password_reset',
            'subject_id' => $user->id,
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'NFLV-6001',
            'password' => 'brand-new-secret-2026',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_reset_with_an_invalid_token_is_rejected_without_changing_the_password(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email' => 'unchanged@nfvl.test',
            'password' => bcrypt('original-password'),
        ]);

        $this->post(route('password.store'), [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'brand-new-secret-2026',
            'password_confirmation' => 'brand-new-secret-2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'account.password_reset']);
    }

    public function test_requesting_a_reset_link_does_not_reveal_whether_an_account_exists(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'someone@nfvl.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'email' => 'mismatch@nfvl.test',
        ]);

        $this->post(route('password.store'), [
            'token' => 'any-token',
            'email' => $user->email,
            'password' => 'brand-new-secret-2026',
            'password_confirmation' => 'a-different-value',
        ])->assertSessionHasErrors('password');
    }
}
