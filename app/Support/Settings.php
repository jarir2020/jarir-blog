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
