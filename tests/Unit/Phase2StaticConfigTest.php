<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Phase 2 — Real content pipeline.
 *
 * Source-level checks that pin the deliverables:
 *   - credentials.txt exists and contains the demo admin login
 *   - storage symlink is in place
 *   - Search.vue and the search route are wired
 *   - PostCard.vue and BlogPost.vue consume the server's reading_time
 *   - DatabaseSeeder has real content (not the placeholder)
 *   - the search and image-upload routes are registered
 */
class Phase2StaticConfigTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__.'/../../';

    public function test_credentials_txt_exists_and_documents_demo_account(): void
    {
        $path = self::PROJECT_ROOT.'credentials.txt';
        $this->assertFileExists($path, 'credentials.txt must exist at the project root.');

        $contents = file_get_contents($path);
        $this->assertStringContainsString('demo@jarir.test', $contents);
        $this->assertStringContainsString('password', $contents);
    }

    public function test_storage_symlink_is_in_place(): void
    {
        $symlink = self::PROJECT_ROOT.'public/storage';
        $this->assertDirectoryExists(
            $symlink,
            'public/storage symlink must exist (run: php artisan storage:link).'
        );
        $this->assertTrue(
            is_link($symlink) || is_dir($symlink),
            'public/storage must be a directory the web server can serve from.'
        );
    }

    public function test_search_view_and_route_are_wired(): void
    {
        $router = file_get_contents(self::PROJECT_ROOT.'resources/js/router/index.js');
        $this->assertStringContainsString("path: '/search'", $router);
        $this->assertStringContainsString("name: 'Search'", $router);
        $this->assertStringContainsString('../views/Search.vue', $router);

        $this->assertFileExists(self::PROJECT_ROOT.'resources/js/views/Search.vue');
    }

    public function test_post_card_consumes_server_reading_time(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'resources/js/components/PostCard.vue');
        $this->assertStringContainsString('post.reading_time', $contents);
    }

    public function test_blog_post_view_consumes_server_reading_time(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'resources/js/views/BlogPost.vue');
        $this->assertStringContainsString('reading_time', $contents);
    }

    public function test_database_seeder_creates_real_content(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'database/seeders/DatabaseSeeder.php');
        $this->assertStringNotContainsString(
            'User::factory(10)->create()',
            $contents,
            'DatabaseSeeder still contains the default placeholder.'
        );
        $this->assertStringContainsString('seedUser', $contents);
        $this->assertStringContainsString('seedCategories', $contents);
        $this->assertStringContainsString('seedTags', $contents);
        $this->assertStringContainsString('seedPosts', $contents);
    }

    public function test_search_and_image_routes_are_registered(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'routes/web.php');
        $this->assertStringContainsString("'/search'", $contents);
        $this->assertStringContainsString('ImageController', $contents);
    }
}
