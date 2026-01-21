<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin role
        Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'description' => 'Full access to all system features',
                'permissions' => [
                    'manage_users',
                    'manage_social_accounts',
                    'manage_posts',
                    'manage_leads',
                    'manage_automation',
                    'view_analytics',
                    'manage_settings'
                ]
            ]
        );

        // Create Tenant role
        Role::updateOrCreate(
            ['slug' => 'tenant'],
            [
                'name' => 'Tenant',
                'description' => 'Limited access for managing own social accounts and content',
                'permissions' => [
                    'manage_own_social_accounts',
                    'create_posts',
                    'view_own_leads',
                    'manage_own_automation',
                    'view_own_analytics'
                ]
            ]
        );
    }
}