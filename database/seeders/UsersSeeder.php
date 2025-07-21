<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 1000 users with varied verification status
        User::factory(800)->create(); // 800 verified users

        User::factory(200)->unverified()->create(); // 200 unverified users

        // Create some admin/test users with specific data
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('test123'),
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'name' => 'Usuario Demo',
            'email' => 'demo@example.com',
            'password' => Hash::make('demo123'),
            'email_verified_at' => null, // Unverified
        ]);

        $this->command->info('Created 1000+ users successfully!');
        $this->command->info('- 800 verified users');
        $this->command->info('- 200 unverified users');
        $this->command->info('- 3 specific test users (admin, test, demo)');
    }
}
