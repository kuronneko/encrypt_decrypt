<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Env;

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
            EncryptEmailsSeeder::class, // Uncomment this line to encrypt existing emails
            LocationSeeder::class, // Seed locations for users
            // Uncomment the line below to encrypt existing emails
            // EncryptEmailsSeeder::class,
        ]);
    }
}
