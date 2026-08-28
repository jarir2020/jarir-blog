<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the blog with realistic content for local dev.
 *
 *   php artisan db:seed
 *
 * Creates:
 *   - 1 demo admin user (see credentials.txt)
 *   - 6 categories
 *   - 15 tags
 *   - 30 published posts, randomly distributed across categories and tags.
 *
 * Re-running is safe: existing rows are matched by slug before insert.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = $this->seedUser();
        $categories = $this->seedCategories();
        $tags = $this->seedTags();
        $this->seedPosts($user, $categories, $tags);
    }

    private function seedUser(): User
    {
        return User::updateOrCreate(
            ['email' => 'demo@jarir.test'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'admin', // Phase 4: the demo account has admin access
            ]
        );
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $names = [
            'Technology',
            'Lifestyle',
            'Travel',
            'Business',
            'Food',
            'Health',
        ];

        $byName = [];
        foreach ($names as $i => $name) {
            $slug = Str::slug($name);
            $byName[$name] = Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => "Articles about $name.",
                    'parent_id' => null,
                ]
            );
        }

        return $byName;
    }

    /**
     * @return array<string, Tag>
     */
    private function seedTags(): array
    {
        $names = [
            'AI', 'Laravel', 'Vue', 'JavaScript', 'Productivity',
            'Wellness', 'Recipes', 'Remote Work', 'Photography',
            'Startups', 'Design', 'Books', 'Travel Tips', 'Fitness', 'Career',
        ];

        $byName = [];
        foreach ($names as $name) {
            $byName[$name] = Tag::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }

        return $byName;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Tag>  $tags
     */
    private function seedPosts(User $user, array $categories, array $tags): void
    {
        $categoryNames = array_keys($categories);
        $tagNames = array_keys($tags);

        for ($i = 1; $i <= 30; $i++) {
            $title = "Sample Post #$i: ".$this->topicFor($i);

            $existing = Post::where('title', $title)->first();
            if ($existing) {
                continue;
            }

            $post = Post::create([
                'author_id' => $user->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => $this->excerptFor($i),
                'content' => $this->bodyFor($i),
                'featured_image' => null,
                'status' => 'published',
                'is_featured' => $i <= 3, // first three are featured
                'published_at' => now()->subHours($i * 5),
            ]);

            $cats = (array) fake()->randomElements($categoryNames, fake()->numberBetween(1, 2));
            $post->categories()->sync(collect($cats)->map(fn ($n) => $categories[$n]->id)->all());

            $postTags = (array) fake()->randomElements($tagNames, fake()->numberBetween(2, 4));
            $post->tags()->sync(collect($postTags)->map(fn ($n) => $tags[$n]->id)->all());
        }
    }

    private function topicFor(int $i): string
    {
        $topics = [
            'The Future of AI in Everyday Tools',
            'Why Side Projects Make You Better at Work',
            'Slow Mornings and Strong Coffee',
            'Three Weeks in the Mountains',
            'A Minimalist Desk Setup That Actually Works',
            'The Hidden Cost of Always-On Notifications',
            'From Idea to MVP in One Weekend',
            'How I Plan My Week on Paper',
            'Reading More by Reading Less',
            'The Joy of Cooking for One',
            'Designing APIs People Want to Use',
            'Why I Switched Back to a Wired Keyboard',
            'Five Tools I No Longer Recommend',
            'Notes from a Quiet Cafe',
            'Building Software That Lasts',
            'The Case for Boring Technology',
            'Walking Meetings Changed My Team',
            'How to Take a Real Lunch Break',
            'A Weekend Without a Phone',
            'Small Systems Beat Big Goals',
            'What I Learned From a Year of Writing Daily',
            'Travel Light, Pack Two Jackets',
            'A Better Way to Track Spending',
            'When to Stop Optimizing',
            'The Quiet Power of Routine',
            'Lessons From My First Failed Startup',
            'Designing for Calm Software',
            'The Best Books I Read This Year',
            'How I Plan a Trip in an Hour',
            'Why Defaults Matter More Than Features',
        ];

        return $topics[($i - 1) % count($topics)];
    }

    private function excerptFor(int $i): string
    {
        return fake()->sentence(14).' In this post we look at the trade-offs and share what worked for us.';
    }

    private function bodyFor(int $i): string
    {
        $paragraphs = [];
        for ($p = 0; $p < 4; $p++) {
            $paragraphs[] = fake()->paragraph(6);
        }

        return implode("\n\n", $paragraphs);
    }
}
