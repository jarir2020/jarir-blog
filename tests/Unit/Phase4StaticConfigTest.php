<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Phase 4 — admin / CMS.
 *
 * Source-level checks for the files Phase 4 added or changed.
 */
class Phase4StaticConfigTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__.'/../../';

    public function test_user_model_has_is_admin_helper(): void
    {
        $user = file_get_contents(self::PROJECT_ROOT.'app/Models/User.php');
        $this->assertStringContainsString("'role'", $user);
        $this->assertStringContainsString('isAdmin', $user);
        $this->assertStringContainsString("'admin'", $user);
    }

    public function test_admin_middleware_class_exists(): void
    {
        $this->assertFileExists(
            self::PROJECT_ROOT.'app/Http/Middleware/EnsureUserIsAdmin.php'
        );
    }

    public function test_bootstrap_registers_admin_middleware_alias(): void
    {
        $bootstrap = file_get_contents(self::PROJECT_ROOT.'bootstrap/app.php');
        $this->assertStringContainsString("'admin'", $bootstrap);
        $this->assertStringContainsString('EnsureUserIsAdmin', $bootstrap);
    }

    public function test_admin_post_controller_exists(): void
    {
        $this->assertFileExists(
            self::PROJECT_ROOT.'app/Http/Controllers/Api/Admin/PostController.php'
        );
    }

    public function test_admin_comment_controller_exists(): void
    {
        $this->assertFileExists(
            self::PROJECT_ROOT.'app/Http/Controllers/Api/Admin/CommentController.php'
        );
    }

    public function test_admin_routes_are_registered_and_protected(): void
    {
        $routes = file_get_contents(self::PROJECT_ROOT.'routes/web.php');
        $this->assertStringContainsString("'admin'", $routes, 'admin middleware alias must be used.');
        $this->assertStringContainsString("'/admin/posts'", $routes);
        $this->assertStringContainsString("'/admin/comments'", $routes);
        $this->assertStringContainsString("'/admin/me'", $routes);
        $this->assertStringContainsString("'/admin/subscribers'", $routes);
        $this->assertStringContainsString("'/admin/tags'", $routes);
        $this->assertStringContainsString("'/admin/stats'", $routes);
    }

    public function test_admin_spa_catchall_route_registered(): void
    {
        $routes = file_get_contents(self::PROJECT_ROOT.'routes/web.php');
        $this->assertStringContainsString("'/admin/{any?}'", $routes);
    }

    public function test_admin_spa_components_exist(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/views/admin/AdminLayout.vue');
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/views/admin/AdminDashboard.vue');
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/views/admin/AdminPosts.vue');
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/views/admin/AdminPostEdit.vue');
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/views/admin/AdminComments.vue');
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/components/admin/AdminSidebar.vue');
    }

    public function test_admin_layout_uses_sidebar_component(): void
    {
        $layout = file_get_contents(self::PROJECT_ROOT.'resources/js/views/admin/AdminLayout.vue');
        $this->assertStringContainsString('AdminSidebar', $layout);
    }

    public function test_admin_dashboard_consumes_stats_endpoint(): void
    {
        $dashboard = file_get_contents(self::PROJECT_ROOT.'resources/js/views/admin/AdminDashboard.vue');
        $this->assertStringContainsString('/api/admin/stats', $dashboard);
    }

    public function test_router_registers_admin_children(): void
    {
        $router = file_get_contents(self::PROJECT_ROOT.'resources/js/router/index.js');
        $this->assertStringContainsString("path: '/admin'", $router);
        $this->assertStringContainsString("name: 'AdminDashboard'", $router);
        $this->assertStringContainsString("name: 'AdminPosts'", $router);
        $this->assertStringContainsString("name: 'AdminPostEdit'", $router);
        $this->assertStringContainsString("name: 'AdminComments'", $router);
    }

    public function test_app_vue_uses_admin_branch(): void
    {
        $app = file_get_contents(self::PROJECT_ROOT.'resources/js/App.vue');
        $this->assertStringContainsString("route.path.startsWith('/admin')", $app);
    }

    public function test_seeder_grants_admin_role(): void
    {
        $seeder = file_get_contents(self::PROJECT_ROOT.'database/seeders/DatabaseSeeder.php');
        $this->assertStringContainsString("'role' => 'admin'", $seeder);
    }

    public function test_credentials_documents_admin_role(): void
    {
        $creds = file_get_contents(self::PROJECT_ROOT.'credentials.txt');
        $this->assertStringContainsString('admin', $creds);
    }
}
