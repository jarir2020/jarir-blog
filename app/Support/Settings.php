<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 10 — typed accessors for site-wide settings.
 *
 * Reads the (key, value) site_settings table and returns the
 * values as plain strings. Each known setting has a typed
 * accessor (siteName(), siteTagline(), contactEmail(), etc.)
 * that returns a sensible default if the row is missing —
 * useful in pre-migration test environments where the table
 * doesn't exist yet.
 *
 * Values are cached for one minute so a request that hits the
 * settings many times (Blad ecomposer + controller + Vue
 * fetch) only does one DB roundtrip. Admin updates invalidate
 * the cache via Setting::saved / Setting::deleted model events.
 */
class Settings
{
    public const CACHE_KEY = 'site.settings';
    public const CACHE_TTL_SECONDS = 60;

    /**
     * Get a setting value by key. Returns the given default if
     * the row doesn't exist (or the table is missing).
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $values = $this->all();
        return $values[$key] ?? $default;
    }

    /**
     * Get all settings as a key => value array.
     */
    public function all(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
            return [];
        }
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return Setting::query()
                ->pluck('value', 'key')
                ->all();
        });
    }

    public function siteName(): string
    {
        return $this->get('site.name', 'Blog')
            ?? config('app.name', 'Blog');
    }

    public function siteTagline(): string
    {
        return $this->get('site.tagline', 'Insightful articles, news, and stories.');
    }

    /**
     * Text shown to visitors with JS disabled. Phase 10b — the
     * no-JS fallback in welcome.blade.php is now admin-editable
     * (and the leading "The blog needs JavaScript" line is part
     * of the same string so admins can rewrite the whole message).
     */
    public function noJsMessage(): string
    {
        return $this->get(
            'site.no_js_message',
            'The blog needs JavaScript to render. Please enable it to browse posts.',
        );
    }

    /**
     * Text shown while the Vue SPA bundle is loading. Short,
     * center-of-screen placeholder.
     */
    public function loadingMessage(): string
    {
        return $this->get('site.loading_message', 'Loading the blog…');
    }

    /**
     * Hex color for the browser's <meta name="theme-color">
     * meta tag (the color of the address bar on mobile Safari /
     * Chrome). Falls back to white when unset.
     */
    public function themeColor(): string
    {
        return $this->get('site.theme_color', '#ffffff');
    }

    public function contactEmail(): string
    {
        return $this->get('contact.email', 'contact@example.com');
    }

    public function contactAddress(): string
    {
        return $this->get('contact.address', '');
    }

    public function contactPhone(): string
    {
        return $this->get('contact.phone', '');
    }

    /**
     * Flush the cache. Called by the model event listener on
     * Setting::saved / deleted so admin edits are visible
     * immediately.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
