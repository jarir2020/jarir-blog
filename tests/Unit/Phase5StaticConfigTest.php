<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Phase 5 — quality.
 *
 * Source-level checks for the CI / lint / format / build deliverables.
 */
class Phase5StaticConfigTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__.'/../../';

    public function test_eslint_config_exists(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'.eslintrc.cjs');
    }

    public function test_prettier_config_exists(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'.prettierrc.json');
        $this->assertFileExists(self::PROJECT_ROOT.'.prettierignore');
    }

    public function test_eslint_uses_vue3_recommended(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'.eslintrc.cjs');
        $this->assertStringContainsString('plugin:vue/vue3-recommended', $contents);
    }

    public function test_package_json_has_lint_and_format_scripts(): void
    {
        $pkg = json_decode(file_get_contents(self::PROJECT_ROOT.'package.json'), true);
        $this->assertArrayHasKey('lint', $pkg['scripts']);
        $this->assertArrayHasKey('lint:fix', $pkg['scripts']);
        $this->assertArrayHasKey('format', $pkg['scripts']);
        $this->assertArrayHasKey('format:check', $pkg['scripts']);
        $this->assertArrayHasKey('build', $pkg['scripts']);
    }

    public function test_pint_config_exists_and_uses_laravel_preset(): void
    {
        $pint = file_get_contents(self::PROJECT_ROOT.'pint.json');
        $this->assertStringContainsString('"preset"', $pint);
        $this->assertStringContainsString('laravel', $pint);
    }

    public function test_composer_json_has_lint_and_stan_scripts(): void
    {
        $composer = json_decode(file_get_contents(self::PROJECT_ROOT.'composer.json'), true);
        $this->assertArrayHasKey('lint', $composer['scripts']);
        $this->assertArrayHasKey('lint:fix', $composer['scripts']);
        $this->assertArrayHasKey('test', $composer['scripts']);
        $this->assertArrayHasKey('stan', $composer['scripts']);
    }

    public function test_phpstan_config_exists(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'phpstan.neon');
        $contents = file_get_contents(self::PROJECT_ROOT.'phpstan.neon');
        $this->assertStringContainsString('level:', $contents);
        $this->assertStringContainsString('paths:', $contents);
    }

    public function test_ci_workflow_exists_and_has_jobs(): void
    {
        $this->assertFileExists(self::PROJECT_ROOT.'.github/workflows/ci.yml');
        $contents = file_get_contents(self::PROJECT_ROOT.'.github/workflows/ci.yml');
        $this->assertStringContainsString('jobs:', $contents);
        $this->assertStringContainsString('test:', $contents);
        $this->assertStringContainsString('frontend:', $contents);
    }

    public function test_ci_workflow_runs_pint_and_lint_and_build(): void
    {
        $contents = file_get_contents(self::PROJECT_ROOT.'.github/workflows/ci.yml');
        $this->assertStringContainsString('pint', $contents);
        $this->assertStringContainsString('npm run lint', $contents);
        $this->assertStringContainsString('npm run build', $contents);
        $this->assertStringContainsString('phpunit', $contents);
    }

    public function test_related_endpoint_is_registered(): void
    {
        $routes = file_get_contents(self::PROJECT_ROOT.'routes/web.php');
        $this->assertStringContainsString("'/posts/{slug}/related'", $routes);
    }

    public function test_related_endpoint_declared_before_show(): void
    {
        $routes = file_get_contents(self::PROJECT_ROOT.'routes/web.php');
        $relatedPos = strpos($routes, "'/posts/{slug}/related'");
        $showPos = strpos($routes, "PostController::class, 'show'");
        $this->assertNotFalse($relatedPos, 'related route must be registered');
        $this->assertNotFalse($showPos, 'show route must be registered');
        $this->assertLessThan(
            $showPos,
            $relatedPos,
            'related route must be declared before the catch-style show route.'
        );
    }
}
