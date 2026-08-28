<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Subscriber as SubscriberModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 — Farabiblog-style features.
 *
 * End-to-end tests for the sidebar feed, author pages, comments, newsletter
 * subscription, view-count tracking, and the RSS / Atom feed.
 */
class Phase3ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_returns_recent_popular_and_tags(): void
    {
        $user = User::factory()->create(['name' => 'Sidebar Tester']);
        $tag = Tag::factory()->create(['name' => 'Laravel']);
        $post = Post::factory()->for($user, 'author')->create([
            'views' => 42,
        ]);
        $post->tags()->attach($tag);

        $response = $this->getJson('/api/sidebar')->assertOk();

        $this->assertCount(1, $response->json('recent'));
        $this->assertSame($post->id, $response->json('recent.0.id'));

        $this->assertCount(1, $response->json('popular'));
        $this->assertSame(42, $response->json('popular.0.views'));

        $this->assertCount(1, $response->json('tags'));
        $this->assertSame('Laravel', $response->json('tags.0.name'));
        $this->assertSame(1, $response->json('tags.0.posts_count'));
    }

    public function test_sidebar_excludes_unused_tags(): void
    {
        Tag::factory()->create(['name' => 'No Posts']);
        $user = User::factory()->create();
        $p = Post::factory()->for($user, 'author')->create();
        $used = Tag::factory()->create(['name' => 'Used']);
        $p->tags()->attach($used);

        $tags = collect($this->getJson('/api/sidebar')->assertOk()->json('tags'));
        $this->assertCount(1, $tags);
        $this->assertSame('Used', $tags->first()['name']);
    }

    public function test_post_show_increments_view_count(): void
    {
        $post = Post::factory()->create(['views' => 0]);

        $this->getJson("/api/posts/{$post->slug}")->assertOk();
        $this->getJson("/api/posts/{$post->slug}")->assertOk();
        $this->getJson("/api/posts/{$post->slug}")->assertOk();

        $this->assertSame(3, $post->fresh()->views);
    }

    public function test_post_show_returns_views_in_payload(): void
    {
        $post = Post::factory()->create(['views' => 7]);
        $this->getJson("/api/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('post.views', 8);
    }

    public function test_author_show_returns_profile_and_post_count(): void
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);
        Post::factory()->count(2)->for($user, 'author')->create();
        Post::factory()->draft()->for($user, 'author')->create();

        $this->getJson('/api/authors/jane-smith')
            ->assertOk()
            ->assertJsonPath('author.name', 'Jane Smith')
            ->assertJsonPath('author.handle', 'jane-smith')
            ->assertJsonPath('author.posts_count', 2);
    }

    public function test_author_show_returns_404_for_unknown_handle(): void
    {
        $this->getJson('/api/authors/no-such-author')->assertNotFound();
    }

    public function test_author_posts_returns_only_published_posts_by_that_author(): void
    {
        $user = User::factory()->create(['name' => 'Post Author']);
        Post::factory()->count(2)->for($user, 'author')->create();
        Post::factory()->draft()->for($user, 'author')->create();
        Post::factory()->for(User::factory(), 'author')->create();

        $this->getJson('/api/authors/post-author/posts')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_post_comments_list_returns_empty_initially(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/api/posts/{$post->slug}/comments")
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_post_comment_create_persists_and_returns_payload(): void
    {
        $post = Post::factory()->create();

        $payload = [
            'name' => 'Commenter',
            'email' => 'commenter@example.com',
            'body' => 'This is a great post!',
        ];

        $this->postJson("/api/posts/{$post->slug}/comments", $payload)
            ->assertCreated()
            ->assertJsonPath('name', 'Commenter')
            ->assertJsonPath('body', 'This is a great post!');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'email' => 'commenter@example.com',
        ]);
    }

    public function test_post_comment_create_validates_input(): void
    {
        $post = Post::factory()->create();

        $this->postJson("/api/posts/{$post->slug}/comments", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'body']);
    }

    public function test_post_comments_endpoint_404s_for_unknown_slug(): void
    {
        $this->getJson('/api/posts/no-such-slug/comments')->assertNotFound();
    }

    public function test_post_comments_strips_html_tags_from_input(): void
    {
        $post = Post::factory()->create();

        $this->postJson("/api/posts/{$post->slug}/comments", [
            'name' => '<b>Evil</b>',
            'email' => 'ok@example.com',
            'body' => '<script>alert(1)</script>normal text',
        ])->assertCreated();

        $stored = Comment::first();
        $this->assertSame('Evil', $stored->name);
        $this->assertStringNotContainsString('<script>', $stored->body);
    }

    public function test_post_comment_list_orders_newest_first(): void
    {
        $post = Post::factory()->create();
        Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'first', 'created_at' => now()->subDay()]);
        Comment::create(['post_id' => $post->id, 'name' => 'B', 'email' => 'b@x.com', 'body' => 'second', 'created_at' => now()]);

        $response = $this->getJson("/api/posts/{$post->slug}/comments")->assertOk();
        $this->assertSame('B', $response->json('data.0.name'));
        $this->assertSame('A', $response->json('data.1.name'));
    }

    public function test_subscribe_creates_subscriber(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'first@example.com'])
            ->assertCreated()
            ->assertJsonPath('email', 'first@example.com');

        $this->assertSame(1, SubscriberModel::count());
    }

    public function test_subscribe_is_idempotent(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'me@example.com'])->assertCreated();
        $this->postJson('/api/subscribe', ['email' => 'me@example.com'])->assertCreated();
        $this->postJson('/api/subscribe', ['email' => 'ME@EXAMPLE.COM'])->assertCreated();

        $this->assertSame(1, SubscriberModel::count(), 'Re-subscription must be a no-op.');
    }

    public function test_subscribe_validates_email(): void
    {
        $this->postJson('/api/subscribe', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_feed_xml_returns_atom_feed_with_latest_posts(): void
    {
        $user = User::factory()->create(['name' => 'Feed Author']);
        $a = Post::factory()->for($user, 'author')->create(['title' => 'A is for Atom']);
        $b = Post::factory()->for($user, 'author')->create(['title' => 'B is for Blog']);
        Post::factory()->draft()->for($user, 'author')->create();

        $response = $this->get('/feed.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/atom+xml', $response->headers->get('Content-Type'));

        $body = $response->getContent();
        $this->assertStringContainsString('http://www.w3.org/2005/Atom', $body);
        $this->assertStringContainsString('<title>Jarir Blog</title>', $body);
        $this->assertStringContainsString('A is for Atom', $body);
        $this->assertStringContainsString('B is for Blog', $body);
        $this->assertStringContainsString($a->slug, $body);
        $this->assertStringNotContainsString('Drafts should be hidden', $body);
    }

    public function test_feed_xml_is_valid_xml(): void
    {
        Post::factory()->create();

        $body = $this->get('/feed.xml')->getContent();

        // Suppress errors so a parse failure surfaces via the assertion.
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($body);
        libxml_use_internal_errors($prev);

        $this->assertNotFalse($doc, 'feed.xml must be valid XML.');
    }
}
