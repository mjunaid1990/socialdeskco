<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = ['Admin', 'Tenant', 'User', 'Editor', 'Viewer'];
        $slugs = ['admin', 'tenant', 'user', 'editor', 'viewer'];
        
        $idx = array_rand($names);
        
        return [
            'name' => $names[$idx],
            'slug' => $slugs[$idx],
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * Indicate that the role is admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrator with full access',
        ]);
    }

    /**
     * Indicate that the role is tenant.
     */
    public function tenant(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Tenant',
            'slug' => 'tenant',
            'description' => 'Business owner or account administrator',
        ]);
    }

    /**
     * Indicate that the role is user.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'User',
            'slug' => 'user',
            'description' => 'Regular user with limited access',
        ]);
    }
}
