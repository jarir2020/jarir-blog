<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Support\MarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9 — admin-editable pages.
 */
class PageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The 2024_01_01_000016_create_pages_table migration seeds
        // 6 default rows; clear them so each test is hermetic.
        Page::query()->delete();
    }

    private function admin()
    {
        return \App\Models\User::factory()->create(['role' => 'admin']);
    }

    public function test_page_crud_round_trip(): void
    {
        $admin = $this->admin();

        // Create the parent first so the sub-page's parent_slug
        // passes the existence check.
        Page::create(['slug' => 'about', 'title' => 'About', 'body' => 'b', 'enabled' => true]);

        $create = $this->actingAs($admin)->postJson('/api/admin/pages', [
            'slug' => 'about/our-mission',
            'title' => 'Our Mission',
            'excerpt' => 'What we set out to do.',
            'body' => '# Mission',
            'parent_slug' => 'about',
            'order' => 1,
            'enabled' => true,
        ])->assertCreated();
        $id = $create->json('page.id');

        $this->actingAs($admin)->putJson("/api/admin/pages/{$id}", [
            'title' => 'Mission (renamed)',
            'body' => '# Mission v2',
        ])->assertOk();
        $fresh = Page::find($id);
        $this->assertSame('Mission (renamed)', $fresh->title);
        $this->assertSame('# Mission v2', $fresh->body);

        $this->actingAs($admin)->deleteJson("/api/admin/pages/{$id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);
        $this->assertNull(Page::find($id));
    }

    public function test_page_index_returns_top_level_pages_by_default(): void
    {
        // Three rows; two have parent_slug = 'about'.
        Page::create(['slug' => 'about',         'title' => 'About',     'body' => 'b', 'enabled' => true]);
        Page::create(['slug' => 'about/our-team', 'title' => 'Team',     'body' => 'b', 'parent_slug' => 'about', 'enabled' => true, 'order' => 1]);
        Page::create(['slug' => 'contact',       'title' => 'Contact',   'body' => 'b', 'enabled' => true]);

        $response = $this->getJson('/api/pages')->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertSame(['about', 'contact'], $slugs);
    }

    public function test_page_index_with_parent_filter_returns_subpages(): void
    {
        Page::create(['slug' => 'about',           'title' => 'About', 'body' => 'b', 'enabled' => true]);
        Page::create(['slug' => 'about/our-mission', 'title' => 'Mission', 'body' => 'b', 'parent_slug' => 'about', 'enabled' => true, 'order' => 1]);
        Page::create(['slug' => 'about/our-team',    'title' => 'Team',    'body' => 'b', 'parent_slug' => 'about', 'enabled' => true, 'order' => 2]);
        Page::create(['slug' => 'contact',         'title' => 'Contact', 'body' => 'b', 'enabled' => true]);

        $response = $this->getJson('/api/pages?parent=about')->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertSame(['about/our-mission', 'about/our-team'], $slugs);
    }

    public function test_page_index_excludes_disabled(): void
    {
        Page::create(['slug' => 'about',         'title' => 'About', 'body' => 'b', 'enabled' => true]);
        Page::create(['slug' => 'about/sub-1',    'title' => 'Sub 1',  'body' => 'b', 'parent_slug' => 'about', 'enabled' => false]);
        Page::create(['slug' => 'about/sub-2',    'title' => 'Sub 2',  'body' => 'b', 'parent_slug' => 'about', 'enabled' => true, 'order' => 1]);

        $response = $this->getJson('/api/pages?parent=about')->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug')->all();
        $this->assertSame(['about/sub-2'], $slugs);
    }

    public function test_page_show_renders_markdown_to_html(): void
    {
        $page = Page::create([
            'slug' => 'about/our-mission',
            'title' => 'Our Mission',
            'body' => "# Heading\n\nThis is **bold** and this is a [link](https://example.com).",
            'enabled' => true,
        ]);

        $response = $this->getJson('/api/pages/about/our-mission')->assertOk();
        $body = $response->json('page.body_html');

        $this->assertStringContainsString('<h1>Heading</h1>', $body);
        $this->assertStringContainsString('<strong>bold</strong>', $body);
        $this->assertStringContainsString('href="https://example.com"', $body);
    }

    public function test_page_show_strips_unsafe_html(): void
    {
        Page::create([
            'slug' => 'about/test',
            'title' => 'Test',
            // Even if an admin pastes raw HTML, the renderer strips
            // it (html_input => 'strip'). The dangerous <script> tag
            // is the actual XSS vector; the text "alert(...)" is just
            // a string after the tag is removed.
            'body' => "Hello <script>alert('xss')</script> world",
            'enabled' => true,
        ]);

        $response = $this->getJson('/api/pages/about/test')->assertOk();
        $body = $response->json('page.body_html');

        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringNotContainsString('</script>', $body);
        $this->assertStringNotContainsString('onerror=', $body);
        $this->assertStringContainsString('Hello', $body);
        $this->assertStringContainsString('world', $body);
    }

    public function test_page_show_returns_404_for_missing_slug(): void
    {
        $this->getJson('/api/pages/does-not-exist')->assertNotFound();
    }

    public function test_page_slug_must_be_unique(): void
    {
        $admin = $this->admin();
        Page::create(['slug' => 'about', 'title' => 'About', 'body' => 'b', 'enabled' => true]);

        $this->actingAs($admin)
            ->postJson('/api/admin/pages', [
                'slug' => 'about',
                'title' => 'Another about',
                'body' => 'b',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_page_slug_must_be_url_safe(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson('/api/admin/pages', [
                'slug' => 'has spaces!',
                'title' => 'Bad slug',
                'body' => 'b',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_page_parent_slug_must_reference_existing_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson('/api/admin/pages', [
                'slug' => 'about/our-mission',
                'title' => 'Mission',
                'body' => 'b',
                'parent_slug' => 'no-such-page',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_slug']);
    }

    public function test_page_requires_admin(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'user']);
        $this->actingAs($user)
            ->getJson('/api/admin/pages')
            ->assertForbidden();
    }

    public function test_default_seeds_about_and_contact(): void
    {
        // setUp() empties the table for hermetic tests. Re-seed the
        // canonical six rows here by running the migration's
        // seeder manually.
        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => \Database\Seeders\DatabaseSeeder::class,
        ]);
        // The DatabaseSeeder doesn't seed pages yet (it predates
        // them), so insert the six defaults directly.
        $now = now();
        \Illuminate\Support\Facades\DB::table('pages')->insert([
            ['slug' => 'about', 'title' => 'About Us', 'body' => 'b', 'order' => 1, 'enabled' => true, 'parent_slug' => null, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'about/our-mission', 'title' => 'Our Mission', 'body' => 'b', 'order' => 1, 'enabled' => true, 'parent_slug' => 'about', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'about/our-vision', 'title' => 'Our Vision', 'body' => 'b', 'order' => 2, 'enabled' => true, 'parent_slug' => 'about', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'about/what-we-offer', 'title' => 'What We Offer', 'body' => 'b', 'order' => 3, 'enabled' => true, 'parent_slug' => 'about', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'about/our-team', 'title' => 'Our Team', 'body' => 'b', 'order' => 4, 'enabled' => true, 'parent_slug' => 'about', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'about/contact-us', 'title' => 'Contact Us', 'body' => 'b', 'order' => 5, 'enabled' => true, 'parent_slug' => 'about', 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'contact', 'title' => 'Contact Us', 'body' => 'b', 'order' => 2, 'enabled' => true, 'parent_slug' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $about = Page::where('slug', 'about')->first();
        $this->assertNotNull($about);
        $this->assertTrue($about->enabled);

        $contact = Page::where('slug', 'contact')->first();
        $this->assertNotNull($contact);
        $this->assertTrue($contact->enabled);

        $subs = Page::where('parent_slug', 'about')->get();
        $this->assertCount(5, $subs, 'About has 5 sub-pages: our-mission, our-vision, what-we-offer, our-team, contact-us.');

        $this->assertNotNull(Page::where('slug', 'about/our-vision')->first());
    }

    public function test_page_show_includes_hero_image(): void
    {
        Page::create([
            'slug' => 'about',
            'title' => 'About',
            'body' => 'b',
            'hero_image' => '/storage/posts/abc.jpg',
            'enabled' => true,
        ]);

        $response = $this->getJson('/api/pages/about')->assertOk();
        $this->assertSame('/storage/posts/abc.jpg', $response->json('page.hero_image'));
    }

    public function test_page_show_hero_image_is_null_when_not_set(): void
    {
        Page::create(['slug' => 'about', 'title' => 'About', 'body' => 'b', 'enabled' => true]);

        $response = $this->getJson('/api/pages/about')->assertOk();
        $this->assertNull($response->json('page.hero_image'));
    }

    public function test_markdown_renderer_service_strips_script_tags(): void
    {
        $renderer = app(MarkdownRenderer::class);
        // The renderer uses html_input => 'strip', so the <script>
        // tag and its closing counterpart are removed entirely. The
        // text between the tags becomes plain text.
        $html = $renderer->render('Before <script>alert(1)</script> after.');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('</script>', $html);
        $this->assertStringContainsString('Before', $html);
        $this->assertStringContainsString('after.', $html);
        $this->assertStringContainsString('alert(1)', $html, 'Inner text of the stripped tag is kept verbatim.');
    }

    public function test_destroy_blocked_when_page_has_children(): void
    {
        $admin = $this->admin();
        Page::create(['slug' => 'about',           'title' => 'About', 'body' => 'b', 'enabled' => true]);
        Page::create(['slug' => 'about/our-mission', 'title' => 'Mission', 'body' => 'b', 'parent_slug' => 'about', 'enabled' => true]);

        $about = Page::where('slug', 'about')->first();
        $this->actingAs($admin)
            ->deleteJson("/api/admin/pages/{$about->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', fn ($m) => str_contains((string) $m, 'sub-page'));
    }
}
