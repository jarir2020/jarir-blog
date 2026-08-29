<?php

namespace Tests\Feature;

use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 — admin widget CRUD.
 *
 * The widgets table backs the public sidebar; the public endpoint
 * tests in Phase3ApiTest cover read-side behaviour. This file
 * covers the admin-side CRUD + auth gates.
 */
class WidgetApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return \App\Models\User::factory()->create(['role' => 'admin']);
    }

    public function test_widget_index_requires_admin(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->getJson('/api/admin/widgets')->assertForbidden();
    }

    public function test_widget_crud_round_trip(): void
    {
        $admin = $this->admin();

        // Create
        $create = $this->actingAs($admin)->postJson('/api/admin/widgets', [
            'name' => 'My Widget',
            'type' => 'html',
            'position' => 'right',
            'order' => 5,
            'enabled' => true,
            'settings' => ['body' => 'hello world'],
        ])->assertCreated();
        $id = $create->json('widget.id');
        $this->assertSame('html', $create->json('widget.type'));
        $this->assertSame('hello world', $create->json('widget.settings.body'));

        // Show
        $this->actingAs($admin)->getJson("/api/admin/widgets/{$id}")
            ->assertOk()
            ->assertJsonPath('widget.name', 'My Widget');

        // Update
        $this->actingAs($admin)->putJson("/api/admin/widgets/{$id}", [
            'name' => 'Renamed',
            'settings' => ['body' => 'updated'],
        ])->assertOk();
        $this->assertSame('Renamed', Widget::find($id)->name);
        $this->assertSame('updated', Widget::find($id)->settings['body']);

        // Delete
        $this->actingAs($admin)->deleteJson("/api/admin/widgets/{$id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);
        $this->assertNull(Widget::find($id));
    }

    public function test_widget_settings_are_cast_to_array(): void
    {
        $admin = $this->admin();
        $create = $this->actingAs($admin)->postJson('/api/admin/widgets', [
            'name' => 'Cat', 'type' => 'category', 'position' => 'right',
            'settings' => ['category_id' => 1, 'title' => 'Featured'],
        ])->assertCreated();

        $widget = Widget::find($create->json('widget.id'));
        $this->assertIsArray($widget->settings);
        $this->assertSame(1, $widget->settings['category_id']);
        $this->assertSame('Featured', $widget->settings['title']);
    }

    public function test_widget_rejects_unknown_type(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson('/api/admin/widgets', [
            'name' => 'Bad', 'type' => 'definitely-not-a-widget',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_widget_can_be_disabled_via_toggle(): void
    {
        $admin = $this->admin();
        $widget = Widget::create([
            'type' => 'html', 'name' => 'Toggle', 'enabled' => true, 'order' => 1,
        ]);

        $this->actingAs($admin)->putJson("/api/admin/widgets/{$widget->id}", [
            'enabled' => false,
        ])->assertOk();
        $this->assertFalse(Widget::find($widget->id)->enabled);

        $widget->delete();
    }
}
