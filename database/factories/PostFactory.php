<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'status' => 'draft',
            'media_files' => [],
            'scheduled_at' => null,
            'published_at' => null,
            'platform_specific_data' => [],
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the post is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(7),
        ]);
    }

    /**
     * Indicate that the post has media files.
     */
    public function withMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_files' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
            ],
        ]);
    }
}
