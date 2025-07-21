<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get existing users or create some if they don't exist
        $users = User::all();

        if ($users->count() === 0) {
            // Create a few users if none exist
            $users = collect([
                User::create([
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => bcrypt('password'),
                ]),
                User::create([
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'password' => bcrypt('password'),
                ]),
                User::create([
                    'name' => 'Bob Wilson',
                    'email' => 'bob@example.com',
                    'password' => bcrypt('password'),
                ]),
            ]);
        }

        // Create one location for each user with encrypted fields
        foreach ($users as $user) {
            Location::create([
                'user_id' => $user->id,
                'name' => "Location for {$user->name}",
                'address' => $this->getRandomAddress(),           // This will be encrypted
                'city' => $this->getRandomCity(),
                'state' => $this->getRandomState(),
                'country' => 'United States',
                'postal_code' => $this->getRandomPostalCode(),    // This will be encrypted
                'region' => $this->getRandomRegion(),             // This will be encrypted
                'latitude' => $this->getRandomLatitude(),
                'longitude' => $this->getRandomLongitude(),
                'notes' => $this->getRandomNotes($user->name),    // This will be encrypted
                'is_active' => rand(0, 1) === 1,
            ]);
        }
    }

    private function getRandomAddress(): string
    {
        $addresses = [
            '123 Main Street',
            '456 Oak Avenue',
            '789 Pine Road',
            '321 Elm Street',
            '654 Maple Drive',
            '987 Cedar Lane',
            '147 Birch Boulevard',
            '258 Walnut Way',
            '369 Cherry Court',
            '741 Ash Circle',
        ];

        return $addresses[array_rand($addresses)];
    }

    private function getRandomCity(): string
    {
        $cities = [
            'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix',
            'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose',
            'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte',
        ];

        return $cities[array_rand($cities)];
    }

    private function getRandomState(): string
    {
        $states = [
            'California', 'Texas', 'Florida', 'New York', 'Pennsylvania',
            'Illinois', 'Ohio', 'Georgia', 'North Carolina', 'Michigan',
            'New Jersey', 'Virginia', 'Washington', 'Arizona', 'Massachusetts',
        ];

        return $states[array_rand($states)];
    }

    private function getRandomPostalCode(): string
    {
        return str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function getRandomRegion(): string
    {
        $regions = [
            'Downtown District',
            'Suburban Area',
            'Industrial Zone',
            'Commercial District',
            'Residential Neighborhood',
            'Business Park',
            'Historic Quarter',
            'Waterfront Area',
            'University District',
            'Shopping Center Area',
        ];

        return $regions[array_rand($regions)];
    }

    private function getRandomNotes(string $userName): string
    {
        $notes = [
            "Private residence for {$userName} - handle with discretion",
            "Business location - contains sensitive client information",
            "Confidential meeting location for {$userName}",
            "Personal storage facility - restricted access",
            "Home office location with sensitive documents",
            "Private consultation space for {$userName}",
            "Secure location for confidential meetings",
            "Personal workspace with private information",
        ];

        return $notes[array_rand($notes)];
    }

    private function getRandomLatitude(): float
    {
        return round(rand(250000, 490000) / 10000, 6); // US latitude range approx 25.0 to 49.0
    }

    private function getRandomLongitude(): float
    {
        return round(rand(-1250000, -660000) / 10000, 6); // US longitude range approx -125.0 to -66.0
    }
}
