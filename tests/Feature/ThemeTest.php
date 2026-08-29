<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Phase 7 — dark / light theme.
 *
 * The Vue theme composable is a pure JS module, so we test it
 * indirectly by hitting the Blade layouts and confirming the
 * FOUC script is in place, plus the dark-class hooks are wired
 * correctly. The actual JS-level behaviour (storage round-trip,
 * class toggling) is exercised manually in the browser since
 * Laravel's JSDOM doesn't natively support the prefers-color-scheme
 * media query.
 */
class ThemeTest extends TestCase
{
    public function test_site_layout_has_fouc_prevention_script(): void
    {
        $html = file_get_contents(
            resource_path('views/components/site/site-layout.blade.php'),
        );

        $this->assertStringContainsString('prefers-color-scheme: dark', $html);
        $this->assertStringContainsString('document.documentElement.classList.add', $html);
        $this->assertStringContainsString("localStorage.getItem('theme')", $html);
    }

    public function test_admin_layout_has_fouc_prevention_script(): void
    {
        $html = file_get_contents(
            resource_path('views/components/admin/admin-layout.blade.php'),
        );

        $this->assertStringContainsString('prefers-color-scheme: dark', $html);
        $this->assertStringContainsString('document.documentElement.classList.add', $html);
    }

    public function test_site_layout_body_has_dark_class_default(): void
    {
        $html = file_get_contents(
            resource_path('views/components/site/site-layout.blade.php'),
        );

        // The body must opt into dark mode variants. We check for the
        // literal token pairs the Tailwind compiler looks for, so a
        // future refactor that breaks one of them is caught.
        $this->assertStringContainsString('bg-gray-50', $html);
        $this->assertStringContainsString('dark:bg-gray-900', $html);
        $this->assertStringContainsString('text-gray-900', $html);
        $this->assertStringContainsString('dark:text-gray-100', $html);
    }

    public function test_theme_toggle_button_is_in_topbar(): void
    {
        $html = file_get_contents(
            resource_path('views/components/site/site-layout.blade.php'),
        );

        $this->assertStringContainsString('id="theme-toggle"', $html);
        $this->assertStringContainsString('theme-icon-sun', $html);
        $this->assertStringContainsString('theme-icon-moon', $html);
    }

    public function test_theme_toggle_handler_is_in_global_script(): void
    {
        $html = file_get_contents(
            resource_path('views/components/site/site-layout.blade.php'),
        );

        $this->assertStringContainsString('window.__theme', $html);
        $this->assertStringContainsString('window.__theme.toggle', $html);
    }

    public function test_admin_layout_body_has_dark_class_default(): void
    {
        $html = file_get_contents(
            resource_path('views/components/admin/admin-layout.blade.php'),
        );

        // Admin main area flips with theme; the dark sidebar stays.
        $this->assertStringContainsString('dark:bg-gray-900', $html);
        $this->assertStringContainsString('dark:bg-gray-800', $html);
    }

    public function test_admin_sidebar_stays_dark_under_light_theme(): void
    {
        // The admin sidebar is editorial chrome — it must remain
        // `bg-gray-900` regardless of theme. This guards against a
        // refactor that accidentally inverts it to white in light mode.
        $html = file_get_contents(
            resource_path('views/components/admin/admin-layout.blade.php'),
        );

        // The literal `bg-gray-900` is in the sidebar's <aside>. We
        // assert it's not prefixed with `dark:`.
        $this->assertMatchesRegularExpression(
            '/<aside[^>]*\bbg-gray-900\b(?![^>]*dark:)/',
            $html,
        );
    }

    public function test_css_declares_dark_color_scheme(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('color-scheme: dark', $css);
    }

    public function test_theme_composable_uses_localStorage(): void
    {
        $js = file_get_contents(resource_path('js/composables/theme.js'));

        $this->assertStringContainsString("STORAGE_KEY = 'theme'", $js);
        $this->assertStringContainsString('localStorage.getItem', $js);
        $this->assertStringContainsString('localStorage.setItem', $js);
        $this->assertStringContainsString('localStorage.removeItem', $js);
        $this->assertStringContainsString("prefers-color-scheme: dark", $js);
    }
}
