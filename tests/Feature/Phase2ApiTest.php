<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 2 — Real content pipeline.
 *
 * Exercises:
 *   - the seeder (idempotent + produces published, featured, related posts)
 *   - the /api/search endpoint
 *   - reading_time attached to every post payload
 *   - the admin image upload endpoint
 */
class Phase2ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_demo_user_with_expected_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'demo@jarir.test',
            'name' => 'Demo Admin',
        ]);
    }

    public function test_seeder_is_idempotent_when_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'demo@jarir.test')->count());
        $this->assertSame(6, Category::count());
        $this->assertSame(15, Tag::count());
        $this->assertSame(30, Post::count(), 'Posts should not duplicate on a second seed.');
    }

    public function test_seeder_creates_featured_and_published_posts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Post::where('is_featured', true)->count());
        $this->assertSame(30, Post::where('status', 'published')->count());
    }

    public function test_seeder_attaches_categories_and_tags_to_posts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $post = Post::first();
        $this->assertGreaterThan(0, $post->categories()->count());
        $this->assertGreaterThan(0, $post->tags()->count());
    }

    public function test_api_search_returns_matches_across_title_excerpt_and_content(): void
    {
        Post::factory()->create(['title' => 'Laravel tips and tricks', 'content' => 'random text']);
        Post::factory()->create(['excerpt' => 'About Vue 3 composition API', 'content' => 'random text']);
        Post::factory()->create(['content' => 'I went hiking in the mountains and it was great']);

        $this->getJson('/api/search?q=laravel')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'Laravel tips and tricks');

        $this->getJson('/api/search?q=vue')
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->getJson('/api/search?q=hiking')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_api_search_is_case_insensitive(): void
    {
        Post::factory()->create(['title' => 'LARVEL vs Laravel']);

        $this->getJson('/api/search?q=laravel')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_api_search_escapes_wildcards_in_query(): void
    {
        Post::factory()->create(['title' => 'A story about 100% dedication']);
        Post::factory()->create(['title' => 'A boring post']);

        $this->getJson('/api/search?q=100%25')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_api_search_with_empty_query_returns_empty_paginator(): void
    {
        Post::factory()->count(3)->create();

        $this->getJson('/api/search?q=')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('data', []);
    }

    public function test_api_search_only_returns_published_posts(): void
    {
        Post::factory()->create(['title' => 'Visible published post']);
        Post::factory()->draft()->create(['title' => 'Hidden draft post']);

        $this->getJson('/api/search?q=post')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_api_posts_includes_reading_time_and_word_count(): void
    {
        Post::factory()->create([
            'content' => str_repeat('word ', 250), // 1.25 → 2 min
        ]);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.reading_time', 2)
            ->assertJsonPath('data.0.word_count', 250);
    }

    public function test_api_post_show_includes_reading_time_and_word_count(): void
    {
        $post = Post::factory()->create(['content' => str_repeat('alpha ', 100)]);

        $this->getJson("/api/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('post.reading_time', 1)
            ->assertJsonPath('post.word_count', 100)
            ->assertJsonStructure(['post' => ['reading_time', 'word_count'], 'related']);
    }

    public function test_api_category_posts_includes_reading_time(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create(['content' => str_repeat('foo ', 600)]); // 3 min
        $post->categories()->attach($category);

        $this->getJson("/api/categories/{$category->slug}/posts")
            ->assertOk()
            ->assertJsonPath('data.0.reading_time', 3)
            ->assertJsonPath('data.0.word_count', 600);
    }

    public function test_admin_image_upload_stores_file_on_public_disk(): void
    {
        Storage::fake('public');
        // Phase 4 — the route is now behind the admin middleware.
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->postJson('/api/admin/images', [
            'image' => UploadedFile::fake()->image('cover.jpg', 800, 600)->size(800),
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['url', 'path', 'bytes']);

        $path = $response->json('path');
        $this->assertStringStartsWith('posts/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_admin_image_upload_rejects_non_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->postJson('/api/admin/images', [
            'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_admin_image_upload_rejects_oversized_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->postJson('/api/admin/images', [
            'image' => UploadedFile::fake()->image('huge.jpg')->size(6 * 1024), // 6 MB > 5 MB cap
        ])->assertStatus(422);
    }

    public function test_admin_image_upload_requires_authentication(): void
    {
        $this->postJson('/api/admin/images', [
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertUnauthorized();
    }
}
