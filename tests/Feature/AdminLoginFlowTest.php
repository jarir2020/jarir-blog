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

    public function test_admin_login_renders_themed_view_not_spa_shell(): void
    {
        // /admin/login must NOT be served by the SPA catch-all. It must
        // be a real, themed Blade page so that someone who lands on
        // /admin/login directly (e.g. a bookmarked URL) sees a working
        // login form, not the empty Vue mount + a router warning.
        $response = $this->get('/admin/login')->assertOk();
        $this->assertStringContainsString('name="email"', $response->getContent());
        $this->assertStringContainsString('name="password"', $response->getContent());
        $this->assertStringContainsString('Admin sign-in', $response->getContent());
        $this->assertStringNotContainsString('No match found', $response->getContent());
    }

    public function test_admin_login_includes_intended_hidden_field(): void
    {
        $response = $this->get('/admin/login?intended=/admin/posts')->assertOk();
        $this->assertStringContainsString(
            'name="intended" value="/admin/posts"',
            $response->getContent()
        );
    }

    public function test_admin_login_omits_intended_when_not_provided(): void
    {
        $response = $this->get('/admin/login')->assertOk();
        $this->assertStringNotContainsString('name="intended"', $response->getContent());
    }

    public function test_admin_login_uses_blog_theme_classes(): void
    {
        $response = $this->get('/admin/login')->assertOk();
        // blue-600 primary button, gray-50 page background, no figtree
        // or indigo-* classes that the stock Breeze layout would emit.
        $this->assertStringContainsString('bg-blue-600', $response->getContent());
        $this->assertStringContainsString('bg-gray-50', $response->getContent());
        $this->assertStringNotContainsString('bg-indigo-500', $response->getContent());
        $this->assertStringNotContainsString('fonts.bunny.net', $response->getContent());
    }

    public function test_login_uses_blog_theme_classes(): void
    {
        $response = $this->get('/login')->assertOk();
        $this->assertStringContainsString('bg-blue-600', $response->getContent());
        $this->assertStringContainsString('bg-gray-50', $response->getContent());
        $this->assertStringNotContainsString('bg-indigo-500', $response->getContent());
        $this->assertStringNotContainsString('fonts.bunny.net', $response->getContent());
    }

    public function test_login_form_preserves_intended_through_post(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        // The form action is /login, so the intended query is lost on
        // POST. The themed view puts it in a hidden form field instead.
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'intended' => '/admin',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_admin_login_uses_dark_header(): void
    {
        $response = $this->get('/admin/login')->assertOk();
        $this->assertStringContainsString('bg-gray-900', $response->getContent());
    }

    public function test_public_login_uses_light_header(): void
    {
        $response = $this->get('/login')->assertOk();
        // The public site-layout uses a white header with a bottom border.
        $this->assertStringContainsString('bg-white', $response->getContent());
        $this->assertStringNotContainsString('bg-gray-900', $response->getContent());
        $this->assertStringNotContainsString('Admin sign-in', $response->getContent());
    }

    public function test_admin_user_lands_on_admin_after_login(): void
    {
        // No ?intended= passed. The controller should detect the admin
        // role and redirect there directly, so users don't have to
        // click through /dashboard to get to their work area.
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_regular_user_lands_on_dashboard_after_login(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_intended_takes_precedence_over_role_default(): void
    {
        // An admin with ?intended=/admin/posts should go to /admin/posts,
        // not /admin. Lets the user land where they were headed.
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->post('/login?intended=/admin/posts', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/posts');
    }
}
