<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customer_reviews')->insert([
            [
                'AccountId' => 1,
                'customer_id' => 707,
                'service_id' => 1,
                'full_name' => 'Ollivia',
                'service_name' => 'Hair cut',
                'rating' => 5,
                'review' => 'Awesome service! Loved the stylist.',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'AccountId' => 1,
                'customer_id' => 708,
                'service_id' => 2,
                'full_name' => 'Maya',
                'service_name' => 'Facial',
                'rating' => 4,
                'review' => 'Nice facial, relaxing experience.',
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
