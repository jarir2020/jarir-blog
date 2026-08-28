<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4 — admin / CMS.
 *
 * Exercises the admin API (posts CRUD, comment moderation) and the
 * admin middleware. The /api/admin/me endpoint and the public endpoints
 * are also covered.
 */
class Phase4ApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function regularUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_admin_route_rejects_unauthenticated(): void
    {
        $this->getJson('/api/admin/posts')->assertUnauthorized();
        $this->getJson('/api/admin/me')->assertUnauthorized();
    }

    public function test_admin_route_rejects_non_admin(): void
    {
        $user = $this->regularUser();
        $this->actingAs($user)->getJson('/api/admin/posts')->assertForbidden();
    }

    public function test_admin_me_returns_user_and_is_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('user.email', $admin->email)
            ->assertJsonPath('is_admin', true);
    }

    public function test_admin_me_for_regular_user_returns_is_admin_false(): void
    {
        $user = $this->regularUser();
        $this->actingAs($user)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('is_admin', false);
    }

    public function test_admin_can_create_a_post(): void
    {
        $admin = $this->admin();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/admin/posts', [
            'title' => 'My first admin post',
            'content' => 'Hello world.',
            'excerpt' => 'A first post',
            'status' => 'published',
            'is_featured' => true,
            'category_ids' => [$category->id],
            'tag_ids' => [$tag->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('post.title', 'My first admin post')
            ->assertJsonPath('post.slug', 'my-first-admin-post')
            ->assertJsonPath('post.is_featured', true)
            ->assertJsonPath('post.status', 'published');

        $post = Post::first();
        $this->assertNotNull($post);
        $this->assertSame($admin->id, $post->author_id);
        $this->assertNotNull($post->published_at, 'published posts must have published_at set.');
        $this->assertSame(1, $post->categories()->count());
        $this->assertSame(1, $post->tags()->count());
    }

    public function test_admin_create_post_validates_input(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson('/api/admin/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content', 'status']);
    }

    public function test_admin_create_post_rejects_invalid_status(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Bad',
                'content' => 'body',
                'status' => 'invalid',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_admin_can_update_a_post(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create(['title' => 'Old title', 'status' => 'draft']);

        $this->actingAs($admin)
            ->putJson("/api/admin/posts/{$post->id}", [
                'title' => 'New title',
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('post.title', 'New title')
            ->assertJsonPath('post.status', 'published');

        $this->assertSame('New title', $post->fresh()->title);
        $this->assertNotNull($post->fresh()->published_at, 'Bumping to published must set published_at.');
    }

    public function test_admin_update_changes_slug_when_title_changes(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create(['title' => 'Old title']);

        $this->actingAs($admin)
            ->putJson("/api/admin/posts/{$post->id}", ['title' => 'A brand new title'])
            ->assertOk()
            ->assertJsonPath('post.slug', 'a-brand-new-title');
    }

    public function test_admin_update_can_keep_existing_slug(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create(['title' => 'Same', 'slug' => 'kept-slug']);

        $this->actingAs($admin)
            ->putJson("/api/admin/posts/{$post->id}", [
                'title' => 'Same',
                'slug' => 'kept-slug',
                'content' => 'still body',
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('post.slug', 'kept-slug');
    }

    public function test_admin_can_delete_a_post(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertNull($post->fresh());
    }

    public function test_admin_post_show_returns_drafts(): void
    {
        $admin = $this->admin();
        $draft = Post::factory()->draft()->create();

        $this->actingAs($admin)
            ->getJson("/api/admin/posts/{$draft->id}")
            ->assertOk()
            ->assertJsonPath('post.id', $draft->id);
    }

    public function test_admin_post_index_includes_drafts(): void
    {
        $admin = $this->admin();
        Post::factory()->count(2)->create();
        Post::factory()->draft()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/posts')
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_admin_post_create_generates_unique_slugs(): void
    {
        $admin = $this->admin();
        Post::factory()->create(['slug' => 'duplicate-title']);

        $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Duplicate title',
                'content' => 'body',
                'status' => 'draft',
            ])
            ->assertCreated()
            ->assertJsonPath('post.slug', 'duplicate-title-2');
    }

    public function test_admin_comments_index_supports_filter(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'a', 'approved' => true]);
        Comment::create(['post_id' => $post->id, 'name' => 'B', 'email' => 'b@x.com', 'body' => 'b', 'approved' => false]);

        $this->actingAs($admin)->getJson('/api/admin/comments?filter=pending')
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->actingAs($admin)->getJson('/api/admin/comments?filter=approved')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_admin_can_approve_a_comment(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        $comment = Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'a', 'approved' => false]);

        $this->actingAs($admin)
            ->postJson("/api/admin/comments/{$comment->id}/approve")
            ->assertOk()
            ->assertJsonPath('comment.approved', true);
    }

    public function test_admin_can_reject_a_comment(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        $comment = Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'a', 'approved' => true]);

        $this->actingAs($admin)
            ->postJson("/api/admin/comments/{$comment->id}/reject")
            ->assertOk()
            ->assertJsonPath('comment.approved', false);
    }

    public function test_admin_can_delete_a_comment(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        $comment = Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'a']);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertNull($comment->fresh());
    }

    public function test_public_comments_endpoint_hides_rejected(): void
    {
        $post = Post::factory()->create();
        Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'visible', 'approved' => true]);
        Comment::create(['post_id' => $post->id, 'name' => 'B', 'email' => 'b@x.com', 'body' => 'hidden', 'approved' => false]);

        $response = $this->getJson("/api/posts/{$post->slug}/comments")->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('A', $names);
        $this->assertNotContains('B', $names);
    }

    public function test_admin_image_upload_requires_admin_role(): void
    {
        Storage::fake('public');
        $user = $this->regularUser();
        $this->actingAs($user)->postJson('/api/admin/images', [
            'image' => UploadedFile::fake()->image('x.jpg'),
        ])->assertForbidden();
    }

    public function test_admin_subscribers_index_paginated(): void
    {
        $admin = $this->admin();
        \App\Models\Subscriber::create(['email' => 'a@x.com', 'subscribed_at' => now()]);
        \App\Models\Subscriber::create(['email' => 'b@x.com', 'subscribed_at' => now()]);

        $this->actingAs($admin)
            ->getJson('/api/admin/subscribers')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }
}
