<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Username or Email');
    }

    public function test_user_can_log_in_with_username(): void
    {
        User::factory()->create([
            'name' => 'Login Test User',
            'username' => 'login-user',
            'email' => 'login@example.test',
            'password' => 'test-password',
            'is_active' => true,
        ]);

        $this->post(route('login.submit'), [
            'login' => 'login-user',
            'password' => 'test-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create([
            'username' => 'inactive-user',
            'password' => 'test-password',
            'is_active' => false,
        ]);

        $this->from(route('login'))
            ->post(route('login.submit'), [
                'login' => 'inactive-user',
                'password' => 'test-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
