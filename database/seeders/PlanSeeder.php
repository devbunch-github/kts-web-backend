<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Plan',
                'price_minor' => 999, // $9.99
                'currency' => 'USD',
                'features' => [
                    '1 user',
                    'Basic support',
                    'Monthly billing',
                ],
                'stripe_plan_id' => 'price_1SLfZMG2MCMdX4izahmTgoin',
                'paypal_plan_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Pro Plan',
                'price_minor' => 1999, // $19.99
                'currency' => 'USD',
                'features' => [
                    'Up to 5 users',
                    'Priority support',
                    'Monthly billing',
                ],
                'stripe_plan_id' => 'price_1SF88yG2MCMdX4izW2ah1vwe',
                'paypal_plan_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise Plan',
                'price_minor' => 4999, // $49.99
                'currency' => 'USD',
                'features' => [
                    'Unlimited users',
                    'Dedicated account manager',
                    'Monthly billing',
                ],
                'stripe_plan_id' => 'price_1SF89NG2MCMdX4izxXF3Vixc',
                'paypal_plan_id' => null,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
