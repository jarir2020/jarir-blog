<?php

namespace Tests\Feature;

use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8 — admin-editable social links.
 *
 * Covers both the public read endpoint and the admin CRUD.
 */
class SocialLinkTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return \App\Models\User::factory()->create(['role' => 'admin']);
    }

    public function test_public_social_links_endpoint_returns_enabled_in_order(): void
    {
        // The migration seeds 3 default rows; clear them so this
        // test is hermetic.
        SocialLink::query()->delete();

        // Seed three rows in a non-sorted order, with one disabled.
        SocialLink::create([
            'platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com',
            'icon' => 'youtube', 'order' => 3, 'enabled' => true,
        ]);
        SocialLink::create([
            'platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com',
            'icon' => 'facebook', 'order' => 1, 'enabled' => true,
        ]);
        SocialLink::create([
            'platform' => 'x', 'label' => 'X', 'url' => 'https://twitter.com',
            'icon' => 'x', 'order' => 2, 'enabled' => false,
        ]);

        $response = $this->getJson('/api/social-links')->assertOk();

        $data = $response->json('data');
        $this->assertCount(2, $data, 'Disabled rows must be excluded.');
        $this->assertSame('Facebook', $data[0]['label']);
        $this->assertSame('YouTube', $data[1]['label']);
    }

    public function test_admin_social_link_crud_round_trip(): void
    {
        $admin = $this->admin();

        // Create
        $create = $this->actingAs($admin)->postJson('/api/admin/social-links', [
            'platform' => 'github',
            'label' => 'GitHub',
            'url' => 'https://github.com/jarir',
            'order' => 5,
            'enabled' => true,
        ])->assertCreated();
        $id = $create->json('social_link.id');
        $this->assertSame('github', $create->json('social_link.icon'));

        // Update
        $this->actingAs($admin)->putJson("/api/admin/social-links/{$id}", [
            'url' => 'https://github.com/another',
            'order' => 1,
        ])->assertOk();
        $this->assertSame('https://github.com/another', SocialLink::find($id)->url);
        $this->assertSame(1, SocialLink::find($id)->order);

        // Delete
        $this->actingAs($admin)->deleteJson("/api/admin/social-links/{$id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);
        $this->assertNull(SocialLink::find($id));
    }

    public function test_admin_social_link_url_must_be_valid(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson('/api/admin/social-links', [
                'platform' => 'github',
                'label' => 'GitHub',
                'url' => 'not a url',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_admin_social_link_rejects_unknown_platform(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson('/api/admin/social-links', [
                'platform' => 'myspace',
                'label' => 'Myspace',
                'url' => 'https://example.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_admin_social_link_requires_admin(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->getJson('/api/admin/social-links')
            ->assertForbidden();
    }

    public function test_admin_social_link_changing_platform_mirrors_icon(): void
    {
        $admin = $this->admin();
        $link = SocialLink::create([
            'platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com',
            'icon' => 'facebook', 'order' => 1, 'enabled' => true,
        ]);

        $this->actingAs($admin)
            ->putJson("/api/admin/social-links/{$link->id}", ['platform' => 'github'])
            ->assertOk();

        $fresh = SocialLink::find($link->id);
        $this->assertSame('github', $fresh->platform);
        $this->assertSame('github', $fresh->icon, 'icon must mirror platform.');
    }

    public function test_admin_social_link_toggle_via_update(): void
    {
        $admin = $this->admin();
        $link = SocialLink::create([
            'platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com',
            'icon' => 'facebook', 'order' => 1, 'enabled' => true,
        ]);

        $this->actingAs($admin)
            ->putJson("/api/admin/social-links/{$link->id}", ['enabled' => false])
            ->assertOk();
        $this->assertFalse(SocialLink::find($link->id)->enabled);
    }
}
