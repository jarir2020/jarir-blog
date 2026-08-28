<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the Breeze login -> admin SPA flow.
 *
 * The SPA hard-navigates to `/login?intended=/admin` when the user
 * hits `/admin` without being signed in. After signing in, the user
 * must be redirected back to /admin, not to the dashboard.
 */
class AdminLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_admin_request_loads_spa_shell(): void
    {
        // /admin/* must return the Vue SPA shell; the SPA itself does
        // the auth check via /api/admin/me and redirects to /login.
        $this->get('/admin')
            ->assertOk()
            ->assertSee('<div id="app">', false);
    }

    public function test_login_route_resolves_to_breeze_create(): void
    {
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/login', 'GET')
        );
        $this->assertSame('login', $route->getName());
        $this->assertStringContainsString('AuthenticatedSessionController', $route->getActionName());
    }

    public function test_login_form_renders_with_email_and_password_inputs(): void
    {
        $response = $this->get('/login')->assertOk();
        $this->assertStringContainsString('name="email"', $response->getContent());
        $this->assertStringContainsString('name="password"', $response->getContent());
        $this->assertStringContainsString('<form', $response->getContent());
    }

    public function test_login_honours_intended_query_param(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/login?intended=/admin', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_login_with_no_intended_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_rejects_external_intended_url(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        // An absolute URL must not be honoured — that's an open-redirect
        // vulnerability waiting to happen.
        $response = $this->post('/login?intended=https://evil.example.com', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertNotEquals('https://evil.example.com', $response->headers->get('Location'));
    }

    public function test_login_rejects_protocol_relative_intended_url(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/login?intended=//evil.example.com', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertStringStartsWith('http://localhost', $response->headers->get('Location'));
    }

    public function test_login_rejects_non_string_intended(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/login?intended[]=foo', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        // Laravel will 500 on array-as-query-string? We should redirect safely.
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_seeded_demo_user_can_log_in_and_reach_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->post('/login?intended=/admin', [
            'email' => 'demo@jarir.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs(User::where('email', 'demo@jarir.test')->first());
    }
}
