<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the UsersSeeder to create 1000+ users
        $this->call([
            UsersSeeder::class,
            // Uncomment the line below to encrypt existing emails
            // EncryptEmailsSeeder::class,
        ]);
    }
}
