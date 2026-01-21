<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Attach Admin role
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }
        
        // Create Demo Tenant User
        $tenantUser = User::updateOrCreate(
            ['email' => 'tenant@example.com'],
            [
                'name' => 'Demo Tenant',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Attach Tenant role
        $tenantRole = Role::where('slug', 'tenant')->first();
        if ($tenantRole) {
            $tenantUser->roles()->syncWithoutDetaching([$tenantRole->id]);
        }
        
        // Create additional demo users for testing
        $demoUsers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'role' => 'tenant'
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'role' => 'tenant'
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
                'role' => 'tenant'
            ],
        ];
        
        foreach ($demoUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'email_verified_at' => now(),
                ]
            );
            
            $role = Role::where('slug', $userData['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}