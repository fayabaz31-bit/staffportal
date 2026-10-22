<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_exposes_the_portal_login(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSeeText('NfLV Staff Portal')
            ->assertSee(route('login'));
    }

    public function test_login_page_is_available_to_guests(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSeeText('Staff number or institutional email');
    }

    public function test_login_page_lists_all_demo_roles(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();

        foreach (UserRole::cases() as $role) {
            $response->assertSeeText($role->label())
                ->assertSeeText($role->demoStaffNumber());
        }
    }

    #[DataProvider('roles')]
    public function test_user_of_each_role_can_sign_in(UserRole $role): void
    {
        $user = User::factory()->create([
            'staff_number' => $role->demoStaffNumber(),
            'email' => 'demo.'.$role->value.'@nfvl.test',
            'role' => $role->value,
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => $role->demoStaffNumber(),
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('roles')]
    public function test_dashboard_shows_the_authenticated_users_role(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role->value]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeText($role->dashboardTitle())
            ->assertSeeText($role->label());

        $serviceTitles = collect($role->dashboardServices())->pluck('title');

        foreach ($role->dashboardServices() as $service) {
            $response->assertSeeText($service['title']);

            if (($service['route_name'] ?? null) !== null) {
                $response->assertSee(route($service['route_name']));
            }
        }

        foreach (UserRole::cases() as $otherRole) {
            if ($otherRole === $role) {
                continue;
            }

            foreach ($otherRole->dashboardServices() as $service) {
                if (! $serviceTitles->contains($service['title'])) {
                    $response->assertDontSeeText($service['title']);
                }
            }
        }
    }

    public function test_local_demo_seeder_creates_one_account_for_each_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        // One account per role, plus the two Registry pilot desks (Registrar and
        // Personnel Officer) that share the HR/Registry role but hold narrower
        // duties.
        $this->assertDatabaseCount('users', count(UserRole::cases()) + 2);

        foreach (UserRole::cases() as $role) {
            $this->assertDatabaseHas('users', [
                'staff_number' => $role->demoStaffNumber(),
                'role' => $role->value,
            ]);
        }

        $this->assertDatabaseHas('users', ['staff_number' => 'NFLV-0009', 'name' => 'Demo Registrar']);
        $this->assertDatabaseHas('users', ['staff_number' => 'NFLV-0010', 'name' => 'Demo Personnel Officer']);
    }

    public function test_active_user_can_sign_in_with_staff_number(): void
    {
        $user = User::factory()->create([
            'staff_number' => 'NFLV-0001',
            'status' => 'active',
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => 'NFLV-0001',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_active_user_can_sign_in_with_email(): void
    {
        $user = User::factory()->create([
            'staff_number' => 'NFLV-0002',
            'status' => 'active',
            'email' => 'staff@example.test',
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => 'staff@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'identifier' => 'nobody@example.test',
            'password' => 'incorrect',
        ]);

        $response->assertRedirectToRoute('login')
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_sign_in(): void
    {
        User::factory()->create([
            'staff_number' => 'NFLV-0003',
            'status' => 'suspended',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'identifier' => 'NFLV-0003',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('login')
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        $credentials = [
            'identifier' => 'NFLV-RATE',
            'password' => 'wrong-password',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), $credentials)
                ->assertSessionHasErrors('identifier');
        }

        // The sixth attempt within the window is throttled rather than evaluated.
        $this->post(route('login.store'), $credentials)->assertStatus(429);
        $this->assertGuest();
    }

    public function test_dashboard_redirects_guests_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create([
            'staff_number' => 'NFLV-0004',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirectToRoute('home');
        $this->assertGuest();
    }

    /** @return array<string, array{UserRole}> */
    public static function roles(): array
    {
        $roles = [];

        foreach (UserRole::cases() as $role) {
            $roles[$role->value] = [$role];
        }

        return $roles;
    }
}
