<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@school.com',
            'password' => 'plain-text-password',
            'role' => User::ROLE_SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);

        // Create admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@school.com',
            'password' => 'plain-text-password',
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        // Create regular user
        User::create([
            'name' => 'User',
            'email' => 'user@school.com',
            'password' => 'plain-text-password',
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        // Create sample users
        User::factory(10)->create([
            'role' => User::ROLE_USER,
        ]);
    }
}
