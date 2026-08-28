<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for /api/admin/stats, the dashboard's single round-trip.
 */
class AdminStatsApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_stats_requires_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->getJson('/api/admin/stats')->assertForbidden();
    }

    public function test_stats_counts_posts_by_status(): void
    {
        $admin = $this->admin();
        Post::factory()->count(3)->create(['status' => 'published']);
        Post::factory()->count(2)->create(['status' => 'draft']);
        Post::factory()->count(1)->create(['status' => 'archived']);

        $response = $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk();

        $this->assertSame(6, $response->json('posts.total'));
        $this->assertSame(3, $response->json('posts.published'));
        $this->assertSame(2, $response->json('posts.draft'));
        $this->assertSame(1, $response->json('posts.archived'));
    }

    public function test_stats_sums_total_views(): void
    {
        $admin = $this->admin();
        Post::factory()->create(['views' => 100]);
        Post::factory()->create(['views' => 50]);

        $this->actingAs($admin)->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('posts.views', 150);
    }

    public function test_stats_counts_comments_split_by_approval(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'a', 'approved' => true]);
        Comment::create(['post_id' => $post->id, 'name' => 'B', 'email' => 'b@x.com', 'body' => 'b', 'approved' => true]);
        Comment::create(['post_id' => $post->id, 'name' => 'C', 'email' => 'c@x.com', 'body' => 'c', 'approved' => false]);

        $response = $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk();

        $this->assertSame(3, $response->json('comments.total'));
        $this->assertSame(1, $response->json('comments.pending'));
        $this->assertSame(2, $response->json('comments.approved'));
    }

    public function test_stats_counts_subscribers(): void
    {
        $admin = $this->admin();
        Subscriber::create(['email' => 'one@x.com', 'subscribed_at' => now()]);
        Subscriber::create(['email' => 'two@x.com', 'subscribed_at' => now()]);

        $this->actingAs($admin)->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('subscribers', 2);
    }

    public function test_stats_counts_users(): void
    {
        $admin = $this->admin();
        User::factory()->count(4)->create();

        $this->actingAs($admin)->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('users', 5); // 4 from the factory + the admin
    }

    public function test_stats_recent_posts_is_limited_to_five(): void
    {
        $admin = $this->admin();
        Post::factory()->count(8)->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk();

        $this->assertCount(5, $response->json('recent_posts'));
    }

    public function test_stats_recent_posts_ordered_by_updated_at(): void
    {
        $admin = $this->admin();
        $older = Post::factory()->create(['updated_at' => now()->subDay()]);
        $newer = Post::factory()->create(['updated_at' => now()]);

        $response = $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk();

        $this->assertSame($newer->id, $response->json('recent_posts.0.id'));
        $this->assertSame($older->id, $response->json('recent_posts.1.id'));
    }

    public function test_stats_recent_posts_eager_loads_author(): void
    {
        $admin = $this->admin();
        $author = User::factory()->create();
        Post::factory()->for($author, 'author')->create();

        $response = $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk();

        $this->assertNotNull($response->json('recent_posts.0.author.id'));
        $this->assertSame($author->id, $response->json('recent_posts.0.author.id'));
    }

    public function test_stats_recent_comments_eager_loads_post(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create();
        Comment::create(['post_id' => $post->id, 'name' => 'A', 'email' => 'a@x.com', 'body' => 'hi']);

        $response = $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk();

        $this->assertNotNull($response->json('recent_comments.0.post.id'));
    }

    public function test_stats_handles_zero_state(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin)->getJson('/api/admin/stats')->assertOk();

        $this->assertSame(0, $response->json('posts.total'));
        $this->assertSame(0, $response->json('comments.total'));
        $this->assertSame(0, $response->json('subscribers'));
        $this->assertSame(0, $response->json('posts.views'));
    }

    public function test_stats_route_is_registered(): void
    {
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/api/admin/stats', 'GET')
        );
        $this->assertStringContainsString('StatsController', $route->getActionName());
    }
}
