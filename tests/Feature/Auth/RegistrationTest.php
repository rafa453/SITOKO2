<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_redirects_guests_to_login(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    public function test_registration_screen_requires_admin(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'cashier']))
            ->get('/register');

        $response->assertStatus(403);
    }

    public function test_admin_can_render_registration_screen(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/register');

        $response->assertStatus(200);
    }
}
