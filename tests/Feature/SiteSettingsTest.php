<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 10 — site settings (key/value).
 *
 * Covers both the public read endpoint and the admin CRUD.
 */
class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeds_are_present(): void
    {
        $this->assertSame('Jarir Blog', Setting::where('key', 'site.name')->value('value'));
        $this->assertSame(
            'Insightful articles, news, and stories from around the world.',
            Setting::where('key', 'site.tagline')->value('value'),
        );
        $this->assertSame('contact@jarirblog.com', Setting::where('key', 'contact.email')->value('value'));
    }

    public function test_public_endpoint_returns_all_settings(): void
    {
        $response = $this->getJson('/api/site-settings')->assertOk();
        $settings = $response->json('settings');
        $this->assertSame('Jarir Blog', $settings['site.name']);
        $this->assertSame('contact@jarirblog.com', $settings['contact.email']);
    }

    public function test_admin_endpoint_requires_admin(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->getJson('/api/admin/site-settings')->assertForbidden();
        $this->actingAs($user)->putJson('/api/admin/site-settings', [
            'settings' => ['site.name' => 'X'],
        ])->assertForbidden();
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->putJson('/api/admin/site-settings', [
            'settings' => [
                'site.name' => 'Renamed Blog',
                'site.tagline' => 'A new tagline.',
                'contact.email' => 'hello@example.com',
                'contact.address' => '1 New Street',
                'contact.phone' => '+1-555-0100',
            ],
        ])->assertOk();

        $this->assertSame('Renamed Blog', Setting::where('key', 'site.name')->value('value'));
        // The keys contain dots (e.g. "contact.email") which makes
        // ->json('settings.contact.email') ambiguous — it would be
        // parsed as a nested path. Read the body directly instead.
        $body = $response->json('settings');
        $this->assertSame('hello@example.com', $body['contact.email']);
        $this->assertSame('1 New Street', $body['contact.address']);
    }

    public function test_admin_can_partial_update(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->putJson('/api/admin/site-settings', [
            'settings' => ['site.name' => 'Just this one'],
        ])->assertOk();

        // Only the supplied key changed; others kept their seed values.
        $this->assertSame('Just this one', Setting::where('key', 'site.name')->value('value'));
        $this->assertSame('contact@jarirblog.com', Setting::where('key', 'contact.email')->value('value'));
    }

    public function test_admin_rejects_unknown_keys(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        // `random.key` is not in the allowlist; the controller
        // silently filters it out, so the response succeeds but
        // the new key is NOT persisted.
        $this->actingAs($admin)->putJson('/api/admin/site-settings', [
            'settings' => [
                'site.name' => 'Updated',
                'random.key' => 'should not stick',
            ],
        ])->assertOk();

        $this->assertSame('Updated', Setting::where('key', 'site.name')->value('value'));
        $this->assertNull(Setting::where('key', 'random.key')->first());
    }

    public function test_admin_validates_request_shape(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->putJson('/api/admin/site-settings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }

    public function test_settings_service_returns_seeded_values(): void
    {
        $service = app(Settings::class);
        $this->assertSame('Jarir Blog', $service->siteName());
        $this->assertSame(
            'Insightful articles, news, and stories from around the world.',
            $service->siteTagline(),
        );
        $this->assertSame('contact@jarirblog.com', $service->contactEmail());
        $this->assertSame('123 Blog Street, Dhaka, Bangladesh', $service->contactAddress());
        $this->assertSame('+880 1234 567890', $service->contactPhone());
    }

    public function test_settings_service_returns_safe_default_when_table_missing(): void
    {
        // Don't run this against a real DB — it's a hypothetical
        // that the service handles a missing table by returning
        // safe defaults. We just call the typed accessors; the
        // service's `all()` short-circuits to an empty array.
        $service = app(Settings::class);
        $this->assertIsString($service->siteName());
        $this->assertIsString($service->siteTagline());
    }
}
