<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Phase 1 — Make it boot and render styled.
 *
 * Static-source checks that pin the config-level fixes:
 *   - Tailwind scans .vue files.
 *   - Post::$fillable includes is_featured.
 *   - README is no longer the default Laravel scaffold.
 *   - Pivot migration files exist.
 *
 * No Laravel boot is required — pure file/string checks.
 */
class Phase1StaticConfigTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__.'/../../';

    public function test_tailwind_config_scans_vue_files(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'tailwind.config.js');

        $this->assertNotFalse($contents);
        $this->assertMatchesRegularExpression(
            '/content\\s*:\\s*\\[[^\\]]*resources\\/js\\/\\*\\*\\/\\*\\.\\{vue,js,ts\\}/s',
            $contents,
            'tailwind.config.js content paths must include resources/js/**/*.{vue,js,ts}.'
        );
    }

    public function test_post_model_includes_is_featured_in_fillable(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'app/Models/Post.php');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString(
            "'is_featured'",
            $contents,
            'Post model must reference is_featured.'
        );
        // The string must be inside the $fillable array, not the scope.
        $this->assertMatchesRegularExpression(
            "/\\\$fillable\\s*=\\s*\\[[^\\]]*'is_featured'[^\\]]*\\]/s",
            $contents,
            'is_featured must be in the Post $fillable array.'
        );
    }

    public function test_post_model_casts_is_featured_to_boolean(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'app/Models/Post.php');

        $this->assertMatchesRegularExpression(
            "/\\\$casts\\s*=\\s*\\[[^\\]]*'is_featured'\\s*=>\\s*'boolean'[^\\]]*\\]/s",
            $contents,
            'Post::$casts must cast is_featured to boolean.'
        );
    }

    public function test_pivot_migrations_exist(): void
    {
        $this->assertFileExists(
            self::PROJECT_ROOT.'database/migrations/2024_01_01_000004_create_category_post_table.php',
            'category_post pivot migration must exist.'
        );
        $this->assertFileExists(
            self::PROJECT_ROOT.'database/migrations/2024_01_01_000005_create_post_tag_table.php',
            'post_tag pivot migration must exist.'
        );
    }

    public function test_pivot_migrations_declare_unique_compound_index(): void
    {
        $categoryPost = file_get_contents(
            self::PROJECT_ROOT.'database/migrations/2024_01_01_000004_create_category_post_table.php'
        );
        $postTag = file_get_contents(
            self::PROJECT_ROOT.'database/migrations/2024_01_01_000005_create_post_tag_table.php'
        );

        $this->assertStringContainsString("unique(['category_id', 'post_id'])", $categoryPost);
        $this->assertStringContainsString("unique(['post_id', 'tag_id'])", $postTag);
    }

    public function test_pivot_migrations_use_cascade_on_delete(): void
    {
        $categoryPost = file_get_contents(
            self::PROJECT_ROOT.'database/migrations/2024_01_01_000004_create_category_post_table.php'
        );
        $postTag = file_get_contents(
            self::PROJECT_ROOT.'database/migrations/2024_01_01_000005_create_post_tag_table.php'
        );

        $this->assertStringContainsString('cascadeOnDelete', $categoryPost);
        $this->assertStringContainsString('cascadeOnDelete', $postTag);
    }

    public function test_readme_is_no_longer_default_laravel_scaffold(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'README.md');

        $this->assertNotFalse($contents);
        $this->assertStringNotContainsString(
            'Laravel is a web application framework with expressive, elegant syntax',
            $contents,
            'README still contains the default Laravel scaffold text.'
        );
        $this->assertStringNotContainsString('# ToDo-App', $contents);
        $this->assertStringNotContainsString('# To-Do', $contents);
        $this->assertStringContainsString('Jarir Blog', $contents);
    }

    public function test_phpunit_uses_in_memory_sqlite_for_tests(): void
    {
        $xml = file_get_contents(self::PROJECT_ROOT.'phpunit.xml');

        $this->assertNotFalse($xml);
        $this->assertStringContainsString(
            '<env name="DB_CONNECTION" value="sqlite"/>',
            $xml,
            'phpunit.xml must pin DB_CONNECTION=sqlite for the test suite.'
        );
        $this->assertStringContainsString(
            '<env name="DB_DATABASE" value=":memory:"/>',
            $xml,
            'phpunit.xml must use :memory: SQLite so tests do not touch the dev database.'
        );
    }
}
