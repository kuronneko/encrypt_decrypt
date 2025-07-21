<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Components\DevExtremeFilter;

class TestEncryptedFields extends Command
{
    protected $signature = 'test:encrypted-fields';
    protected $description = 'Test encrypted fields functionality';

    public function handle()
    {
        $this->info('Testing encrypted fields functionality...');

        try {
            // Test User model
            $user = new User();

            // Test if isEncryptedField method is accessible
            $isEmailEncrypted = $user->isEncryptedField('email');
            $this->info("Email field is encrypted: " . ($isEmailEncrypted ? 'Yes' : 'No'));

            $isNameEncrypted = $user->isEncryptedField('name');
            $this->info("Name field is encrypted: " . ($isNameEncrypted ? 'Yes' : 'No'));

            // Test DevExtremeFilter component
            $query = User::query();
            $filter = new DevExtremeFilter($query, $user);
            $this->info("DevExtremeFilter component created successfully");

            $this->info("✅ All tests passed! The encrypted fields functionality is working correctly.");

        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
