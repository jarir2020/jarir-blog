<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Phase 0 — Stop the bleeding.
 *
 * Confirms the SPA catch-all and the /api/* routes still resolve after the
 * broken BlogController import was removed from routes/web.php. These are
 * integration checks that exercise the route file end-to-end, complementing
 * the static checks in Tests\Unit\Phase0CleanupTest.
 */
class Phase0RoutesTest extends TestCase
{
    /**
     * The / catch-all must serve the welcome view (which mounts the Vue SPA).
     */
    public function test_root_route_serves_welcome_view(): void
    {
        $this->get('/')->assertStatus(200)->assertViewIs('welcome');
    }

    /**
     * Arbitrary SPA paths must be caught by the {any?} route and also
     * serve the welcome view.
     */
    public function test_spa_catchall_serves_welcome_view(): void
    {
        $this->get('/blog/some-slug')->assertStatus(200)->assertViewIs('welcome');
        $this->get('/about')->assertStatus(200)->assertViewIs('welcome');
    }

    /**
     * The blog API endpoints must be registered.
     */
    public function test_api_posts_route_is_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $this->assertTrue(
            $routes->contains(fn ($r) => $r->uri() === 'api/posts' && in_array('GET', $r->methods(), true)),
            'GET /api/posts must be registered.'
        );
    }

    public function test_api_categories_route_is_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $this->assertTrue(
            $routes->contains(fn ($r) => $r->uri() === 'api/categories' && in_array('GET', $r->methods(), true)),
            'GET /api/categories must be registered.'
        );
    }

    public function test_api_posts_show_route_is_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $this->assertTrue(
            $routes->contains(fn ($r) => $r->uri() === 'api/posts/{slug}' && in_array('GET', $r->methods(), true)),
            'GET /api/posts/{slug} must be registered.'
        );
    }

    public function test_api_category_posts_route_is_registered(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $this->assertTrue(
            $routes->contains(fn ($r) => $r->uri() === 'api/categories/{slug}/posts' && in_array('GET', $r->methods(), true)),
            'GET /api/categories/{slug}/posts must be registered.'
        );
    }

    /**
     * Routes should load without errors. If the broken BlogController import
     * ever creeps back in, route resolution will throw a fatal error here.
     */
    public function test_route_loader_does_not_throw(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertGreaterThan(10, count($routes), 'Route table should be populated.');
    }
}
