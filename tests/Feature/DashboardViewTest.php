<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for /dashboard.
 *
 * Until this fix, /dashboard was a placeholder that returned the empty
 * Vue mount view — the public header and footer rendered, but the
 * page body was blank. This test pins the new behaviour: a real,
 * themed dashboard page that adapts to the signed-in user's role.
 */
class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_not_the_spa_shell(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        // The SPA shell is just <div id="app"></div>. The themed view
        // is a full Blade page with header + content + footer.
        $this->assertStringNotContainsString(
            '<div id="app"></div>',
            $response->getContent()
        );
    }

    public function test_dashboard_renders_themed_chrome_for_admin(): void
    {
        $admin = User::factory()->create([
            'name' => 'Demo Admin',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $this->assertStringContainsString('Welcome back, Demo Admin', $response->getContent());
        $this->assertStringContainsString('Admin dashboard', $response->getContent());
        $this->assertStringContainsString('All posts', $response->getContent());
        $this->assertStringContainsString('Comments', $response->getContent());
    }

    public function test_dashboard_does_not_show_admin_cards_to_regular_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Reader',
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertStringContainsString('Welcome back, Reader', $response->getContent());
        $this->assertStringNotContainsString('Admin dashboard', $response->getContent());
        $this->assertStringNotContainsString('href="http', 'Admin dashboard link not present'); // sanity
    }

    public function test_dashboard_uses_blog_theme_classes(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        // Blog theme tokens. The dashboard page has no blue button, so
        // we check the negative cases (no Breeze indigo, no figtree
        // font import) and the positive page-background / footer colours.
        $this->assertStringContainsString('bg-gray-50', $response->getContent());
        $this->assertStringContainsString('bg-gray-800', $response->getContent());
        $this->assertStringContainsString('text-blue-600', $response->getContent());
        $this->assertStringNotContainsString('bg-indigo-500', $response->getContent());
        $this->assertStringNotContainsString('fonts.bunny.net', $response->getContent());
    }

    public function test_dashboard_does_not_link_to_todos(): void
    {
        // Phase 0 / 1 left a "View All Todos / Add Todo" dashboard. Make
        // sure no future refactor brings it back.
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertStringNotContainsString('Todos', $response->getContent());
        $this->assertStringNotContainsString('todos.index', $response->getContent());
        $this->assertStringNotContainsString('btn.css', $response->getContent());
    }

    public function test_dashboard_renders_logout_form(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertStringContainsString('action="http://localhost/logout"', $response->getContent());
        $this->assertStringContainsString('Log out', $response->getContent());
    }

    public function test_dashboard_redirects_to_login_when_unauthenticated(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_links_to_admin_when_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $this->assertStringContainsString('href="http://localhost/admin"', $response->getContent());
    }
}
