<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Stripe\StripeClient;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createSubscriptionSession(Plan $plan, User $user)
    {
        return $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer_email' => $user->email,
            'line_items' => [[
                'price' => $plan->stripe_plan_id,
                'quantity' => 1,
            ]],
            'success_url' => env('FRONTEND_URL') . '/subscription/set-password?user_id=' . $user->id . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => env('FRONTEND_URL') . '/payment-cancelled?user_id=' . $user->id,
        ]);

    }
}
