<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function cashier(): User
    {
        return User::factory()->create(['role' => 'cashier', 'status' => 'active']);
    }

    public function test_tc_auth_001_login_success(): void
    {
        $user = $this->admin();
        $res = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $res->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['type' => 'LOGIN', 'user_id' => $user->id]);
    }

    public function test_tc_auth_002_login_wrong_password(): void
    {
        $user = $this->admin();
        $res = $this->post('/login', ['email' => $user->email, 'password' => 'wrongpass']);
        $res->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tc_auth_003_login_rate_limit(): void
    {
        $user = $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }
        $res = $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $res->assertSessionHasErrors('email');
        $msg = $res->getSession()->get('errors')->get('email')[0];
        $this->assertTrue(str_contains(strtolower($msg), 'too many') || str_contains(strtolower($msg), 'seconds') || str_contains($msg, 'menit'), $msg);
    }

    public function test_tc_auth_004_login_inactive_rejected(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);
        $res = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $res->assertSessionHasErrors('email');
        $this->assertStringContainsString('dinonaktifkan', $res->getSession()->get('errors')->get('email')[0]);
        $this->assertGuest();
    }

    public function test_tc_auth_005_admin_can_register(): void
    {
        $admin = $this->admin();
        $res = $this->actingAs($admin)->post('/register', [
            'name' => 'Test User', 'email' => 'test@test.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ]);
        $res->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'test@test.com']);
    }

    public function test_tc_auth_006_cashier_cannot_register(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier)->get('/register')->assertStatus(403);
        $this->actingAs($cashier)->post('/register', [
            'name' => 'X', 'email' => 'x@x.com', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertStatus(403);
    }

    public function test_tc_auth_007_logout_success(): void
    {
        $user = $this->admin();
        $res = $this->actingAs($user)->post('/logout');
        $res->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', [
            'type' => 'LOGOUT',
            'user_id' => $user->id,
        ]);
    }

    public function test_tc_auth_008_forgot_password_link(): void
    {
        Notification::fake();
        $user = $this->admin();
        $res = $this->post('/forgot-password', ['email' => $user->email]);
        $res->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_tc_auth_009_update_password(): void
    {
        $user = $this->admin();
        $res = $this->actingAs($user)->from('/profile')->put('/password', [
            'current_password' => 'password',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);
        $res->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPass123!', $user->refresh()->password));
    }

    public function test_tc_sec_003_password_hashed(): void
    {
        $user = User::factory()->create();
        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_tc_sec_002_rate_limit_active(): void
    {
        $user = $this->admin();
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }
        $res = $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $res->assertSessionHasErrors('email');
    }
}
