<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 — Make it boot and render styled.
 *
 * Exercises the /api/* blog endpoints against a real (in-memory) database
 * seeded via the new PostFactory/CategoryFactory/TagFactory. Pins the
 * end-to-end contract the Vue SPA now depends on.
 */
class Phase1ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_posts_returns_paginator_with_only_published_posts(): void
    {
        Post::factory()->count(3)->create();
        Post::factory()->draft()->create();

        $response = $this->getJson('/api/posts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'title', 'slug', 'excerpt', 'content']],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);

        $this->assertSame(3, $response->json('total'), 'Drafts must be excluded.');
    }

    public function test_api_posts_orders_by_published_at_desc(): void
    {
        $older = Post::factory()->create(['published_at' => now()->subDays(5)]);
        $newer = Post::factory()->create(['published_at' => now()->subDay()]);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    public function test_api_posts_includes_author_categories_and_tags(): void
    {
        $post = Post::factory()
            ->for(User::factory(), 'author')
            ->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.author.id', $post->author->id)
            ->assertJsonPath('data.0.categories.0.id', $category->id)
            ->assertJsonPath('data.0.tags.0.id', $tag->id);
    }

    public function test_api_posts_show_returns_post_and_related(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category);
        $related = Post::factory()->create();
        $related->categories()->attach($category);

        $response = $this->getJson("/api/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonPath('post.id', $post->id)
            ->assertJsonPath('post.slug', $post->slug)
            ->assertJsonStructure(['post' => ['id', 'title', 'slug'], 'related' => [['id', 'title']]]);

        $relatedIds = collect($response->json('related'))->pluck('id')->all();
        $this->assertContains($related->id, $relatedIds, 'Related posts must include a same-category post.');
    }

    public function test_api_posts_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/posts/does-not-exist')->assertNotFound();
    }

    public function test_api_posts_show_excludes_drafts(): void
    {
        $draft = Post::factory()->draft()->create();

        $this->getJson("/api/posts/{$draft->slug}")->assertNotFound();
    }

    public function test_api_categories_returns_categories_with_post_count(): void
    {
        $category = Category::factory()->create();
        Post::factory()->count(2)->create()->each(
            fn (Post $post) => $post->categories()->attach($category)
        );

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('0.id', $category->id)
            ->assertJsonPath('0.posts_count', 2);
    }

    public function test_api_category_posts_returns_only_paginated_posts_in_category(): void
    {
        $category = Category::factory()->create();
        $inCategory = Post::factory()->count(2)->create();
        $inCategory->each(fn (Post $post) => $post->categories()->attach($category));
        Post::factory()->create(); // not in the category

        $response = $this->getJson("/api/categories/{$category->slug}/posts");

        $response->assertOk();
        $this->assertSame(2, $response->json('total'));
        $this->assertCount(2, $response->json('data'));
    }

    public function test_api_category_posts_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/categories/no-such-category/posts')->assertNotFound();
    }

    public function test_pivot_tables_cascade_delete_with_parent(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category);

        $this->assertDatabaseCount('category_post', 1);

        $post->delete();

        $this->assertDatabaseCount('category_post', 0);
    }

    public function test_category_post_pivot_cascades_when_category_deleted(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create();
        $post->categories()->attach($category);

        $category->delete();

        $this->assertDatabaseCount('category_post', 0);
    }

    public function test_post_is_featured_is_mass_assignable_and_cast_to_boolean(): void
    {
        $author = User::factory()->create();

        $post = Post::create([
            'author_id' => $author->id,
            'title' => 'Featured test',
            'slug' => 'featured-test',
            'content' => 'Body',
            'is_featured' => 1,
            'status_id' => \App\Models\Status::where('slug', 'published')->value('id'),
            'published_at' => now(),
        ]);

        $this->assertTrue($post->is_featured, 'is_featured must be cast to bool.');
        $this->assertSame(1, $post->getAttributes()['is_featured']);
    }
}
