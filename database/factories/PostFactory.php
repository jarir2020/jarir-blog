<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 *
 * Note: this factory does NOT auto-attach categories or tags. Tests should
 * call $post->categories()->attach(...) / $post->tags()->attach(...)
 * explicitly so the test owns the relation setup. Auto-attaching made
 * unique-constraint assertions impossible to write.
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);

        return [
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(100, 99999),
            'excerpt' => $this->faker->paragraph(),
            'content' => implode("\n\n", $this->faker->paragraphs(4)),
            'featured_image' => null,
            'status' => 'published',
            'is_featured' => false,
            'published_at' => now()->subMinutes($this->faker->numberBetween(1, 10000)),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function featured(): self
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
