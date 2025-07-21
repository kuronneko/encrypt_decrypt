<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptEmailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting email encryption process...');

        // Get all users with unencrypted emails
        $users = User::all();
        $totalUsers = $users->count();
        $encryptedCount = 0;

        $this->command->info("Found {$totalUsers} users to process.");

        // Process users in chunks for better memory management
        User::chunk(100, function ($userChunk) use (&$encryptedCount) {
            foreach ($userChunk as $user) {
                try {
                    // Check if email is already encrypted by trying to decrypt it
                    $isAlreadyEncrypted = false;
                    try {
                        Crypt::decryptString($user->email);
                        $isAlreadyEncrypted = true;
                    } catch (\Exception $e) {
                        // Email is not encrypted, continue with encryption
                        $isAlreadyEncrypted = false;
                    }

                    if (!$isAlreadyEncrypted) {
                        // Store original email before encryption
                        $originalEmail = $user->email;

                        // Encrypt the email
                        $encryptedEmail = Crypt::encryptString($originalEmail);

                        // Update the user record directly in database
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['email' => $encryptedEmail]);

                        $encryptedCount++;

                        $this->command->line("Encrypted email for user ID {$user->id}: {$originalEmail}");
                    } else {
                        $this->command->line("Email already encrypted for user ID {$user->id}");
                    }

                } catch (\Exception $e) {
                    $this->command->error("Failed to encrypt email for user ID {$user->id}: " . $e->getMessage());
                }
            }
        });

        $this->command->info("Email encryption completed!");
        $this->command->info("Total users processed: {$totalUsers}");
        $this->command->info("Emails encrypted: {$encryptedCount}");
        $this->command->info("Emails already encrypted: " . ($totalUsers - $encryptedCount));
    }
}
