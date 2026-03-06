<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platforms = ['facebook', 'instagram', 'x', 'linkedin', 'tiktok', 'youtube'];
        
        return [
            'user_id' => User::factory(),
            'platform' => $this->faker->randomElement($platforms),
            'account_id' => $this->faker->unique()->numerify('###############'),
            'username' => $this->faker->userName(),
            'display_name' => $this->faker->name(),
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_expires_at' => now()->addDays(30),
            'is_active' => true,
            'profile_data' => [
                'id' => $this->faker->numerify('###############'),
                'name' => $this->faker->name(),
                'profile_pic' => 'https://example.com/pic.jpg',
            ],
        ];
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the account is for a specific platform.
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'facebook',
        ]);
    }

    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'instagram',
        ]);
    }

    public function x(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'x',
        ]);
    }

    public function linkedin(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'linkedin',
        ]);
    }

    public function tiktok(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'tiktok',
        ]);
    }

    public function youtube(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'youtube',
        ]);
    }
}
