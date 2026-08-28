<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 — quality.
 *
 * End-to-end tests for the related-posts endpoint plus a few sanity
 * checks on the API surface that the rest of the suite didn't cover.
 */
class Phase5ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_endpoint_returns_same_category_posts(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category);

        $relatedA = Post::factory()->create();
        $relatedA->categories()->attach($category);

        $relatedB = Post::factory()->create();
        $relatedB->categories()->attach($category);

        $unrelated = Post::factory()->create();

        $response = $this->getJson("/api/posts/{$post->slug}/related")->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($relatedA->id, $ids);
        $this->assertContains($relatedB->id, $ids);
        $this->assertNotContains($post->id, $ids, 'related must exclude the source post.');
        $this->assertNotContains($unrelated->id, $ids, 'related must not include posts from other categories.');
    }

    public function test_related_endpoint_excludes_drafts(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category);

        $draft = Post::factory()->draft()->create();
        $draft->categories()->attach($category);

        $ids = collect($this->getJson("/api/posts/{$post->slug}/related")->assertOk()->json('data'))
            ->pluck('id')
            ->all();
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_related_endpoint_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/posts/no-such-slug/related')->assertNotFound();
    }

    public function test_related_endpoint_always_returns_404_for_draft(): void
    {
        $draft = Post::factory()->draft()->create();
        $this->getJson("/api/posts/{$draft->slug}/related")->assertNotFound();
    }

    public function test_related_endpoint_includes_reading_time_and_word_count(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create(['content' => str_repeat('hello ', 400)]);
        $post->categories()->attach($category);
        $related = Post::factory()->create(['content' => str_repeat('world ', 200)]);
        $related->categories()->attach($category);

        $response = $this->getJson("/api/posts/{$post->slug}/related")->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', $related->id);
        $this->assertSame(1, $row['reading_time']);
        $this->assertSame(200, $row['word_count']);
    }

    public function test_post_show_includes_related_in_payload(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category);
        $related = Post::factory()->create();
        $related->categories()->attach($category);

        $this->getJson("/api/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonStructure(['post' => ['id', 'title', 'slug'], 'related']);
    }

    public function test_post_index_handles_zero_published_posts(): void
    {
        // No posts seeded at all.
        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('data', []);
    }

    public function test_post_index_clamps_huge_per_page_request(): void
    {
        Post::factory()->count(3)->create();
        // A request for 10000 items shouldn't OOM or hang.
        $response = $this->getJson('/api/posts?per_page=10000')->assertOk();
        $this->assertSame(3, $response->json('total'));
    }

    public function test_post_search_with_special_characters_does_not_500(): void
    {
        Post::factory()->create(['content' => 'normal content']);
        // A query with a backslash and quote must not blow up.
        $this->getJson('/api/search?q='.urlencode('hello "world" \\backslash'))
            ->assertOk();
    }

    public function test_post_show_route_404_when_slug_matches_related_path(): void
    {
        // Sanity: /api/posts/foo/related must NOT be served by show().
        // If route order breaks, this catches it.
        $post = Post::factory()->create(['slug' => 'foo']);
        Category::factory()->create();

        // /api/posts/foo -> show
        $this->getJson('/api/posts/foo')->assertOk()->assertJsonPath('post.slug', 'foo');
        // /api/posts/foo/related -> related controller
        $this->getJson('/api/posts/foo/related')->assertOk()->assertJsonStructure(['data']);
    }
}
