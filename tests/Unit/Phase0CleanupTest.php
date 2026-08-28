<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Phase 0 — Stop the bleeding.
 *
 * The previous LLM pass left a dangling `use App\Http\Controllers\BlogController;`
 * import in routes/web.php (no such class exists) and committed a Vite HMR
 * socket at public/hot. These tests pin those regressions so they cannot
 * silently come back.
 *
 * This is a true unit test — it does not boot Laravel. All paths are
 * resolved relative to the project root via dirname(__DIR__, 2).
 */
class Phase0CleanupTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__.'/../../';

    /**
     * The web routes file must not reference the never-created BlogController.
     * Catches re-introduction of the dead import.
     */
    public function test_web_routes_does_not_import_missing_blog_controller(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'routes/web.php');

        $this->assertNotFalse($contents, 'routes/web.php must be readable');
        $this->assertStringNotContainsString(
            'App\\Http\\Controllers\\BlogController',
            $contents,
            'routes/web.php still imports App\\Http\\Controllers\\BlogController — that class does not exist.'
        );
        $this->assertStringNotContainsString(
            'BlogController',
            $contents,
            'routes/web.php still references the (non-existent) BlogController class.'
        );
    }

    /**
     * The web routes file must import the controllers it actually uses.
     */
    public function test_web_routes_imports_real_controllers(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'routes/web.php');

        $this->assertStringContainsString(
            'App\\Http\\Controllers\\ProfileController',
            $contents,
            'routes/web.php must import ProfileController for the auth profile routes.'
        );
        $this->assertStringContainsString(
            'App\\Http\\Controllers\\Api\\PostController',
            $contents,
            'routes/web.php must import Api\\PostController for the /api/posts routes.'
        );
    }

    /**
     * public/hot is a Vite HMR socket file. It must be git-ignored so it
     * never sneaks back into the index.
     */
    public function test_public_hot_is_git_ignored(): void
    {
        $gitignore = file_get_contents(self::PROJECT_ROOT.'.gitignore');

        $this->assertNotFalse($gitignore, '.gitignore must be readable');
        $this->assertStringContainsString(
            'public/hot',
            $gitignore,
            '.gitignore must list public/hot so the Vite HMR socket is not committed.'
        );
    }
}
