<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample client user
        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Test Client',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
            ]
        );

        // Assign customer role
        $client->assignRole('customer');

        echo "Customer user created successfully.\n";
    }
}