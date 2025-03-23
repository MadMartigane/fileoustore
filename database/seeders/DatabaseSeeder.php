<?php

declare(strict_types=1);

namespace Database\Seeders;

use FileouStore\Services\UserService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user using SleekDB
        $userService = new UserService();

        // Check if admin already exists
        $existingAdmin = $userService->findByEmail('admin@example.com');

        if (!$existingAdmin) {
            $userService->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => 'password', // Change this in production!
                'is_admin' => true,
            ]);

            $this->command->info('Admin user created successfully!');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}

