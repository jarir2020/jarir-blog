<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Phase 3 — Farabiblog-style features.
 *
 * Source-level checks for the files Phase 3 added or changed.
 */
class Phase3StaticConfigTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__.'/../../';

    public function test_sidebar_component_exists(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/components/Sidebar.vue');
    }

    public function test_comment_list_component_exists(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/components/CommentList.vue');
    }

    public function test_author_view_exists(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/views/Author.vue');
    }

    public function test_router_has_author_route(): void
    {
        $router = file_get_contents(self::PROJECT_ROOT.'resources/js/router/index.js');
        $this->assertStringContainsString("path: '/author/:username'", $router);
        $this->assertStringContainsString("name: 'Author'", $router);
    }

    public function test_welcome_blade_has_open_graph_and_twitter_meta(): void
    {
        $blade = file_get_contents(self::PROJECT_ROOT.'resources/views/welcome.blade.php');
        $this->assertStringContainsString('og:title', $blade);
        $this->assertStringContainsString('og:description', $blade);
        $this->assertStringContainsString('twitter:card', $blade);
        $this->assertStringContainsString('application/atom+xml', $blade);
    }

    public function test_app_footer_links_to_rss(): void
    {
        $app = file_get_contents(self::PROJECT_ROOT.'resources/js/App.vue');
        $this->assertStringContainsString('/feed.xml', $app);
    }

    public function test_use_api_exposes_phase3_helpers(): void
    {
        $api = file_get_contents(self::PROJECT_ROOT.'resources/js/composables/useApi.js');
        foreach (['getSidebar', 'getAuthor', 'getAuthorPosts', 'getComments', 'postComment', 'subscribe'] as $fn) {
            $this->assertStringContainsString($fn, $api, "useApi must expose {$fn}().");
        }
    }

    public function test_routes_file_registers_phase3_endpoints(): void
    {
        $routes = file_get_contents(self::PROJECT_ROOT.'routes/web.php');
        $this->assertStringContainsString("'/sidebar'", $routes);
        $this->assertStringContainsString("'/authors/{username}'", $routes);
        $this->assertStringContainsString("'/posts/{slug}/comments'", $routes);
        $this->assertStringContainsString("'/subscribe'", $routes);
        $this->assertStringContainsString("'/feed.xml'", $routes);
    }

    public function test_feed_route_is_outside_api_prefix(): void
    {
        $routes = file_get_contents(self::PROJECT_ROOT.'routes/web.php');

        // Remove the entire `prefix('api')->group(...)` block, then check
        // that the feed route is still present in the remaining content.
        $stripped = preg_replace(
            "/Route::prefix\('api'\)->group\(function[^{]*\{(.*?)\}\);/s",
            '',
            $routes
        );

        $this->assertNotNull($stripped, 'Could not parse the routes file.');
        $this->assertStringContainsString(
            "Route::get('/feed.xml'",
            $stripped,
            'feed.xml must be a top-level route, not nested inside prefix(api).'
        );
    }

    public function test_post_model_exposes_views_and_popular_scope(): void
    {
        $post = file_get_contents(self::PROJECT_ROOT.'app/Models/Post.php');
        $this->assertStringContainsString("'views'", $post);
        $this->assertStringContainsString('scopePopular', $post);
    }
}
