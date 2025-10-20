<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Stripe\StripeClient;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Subscription as StripeSubscription;


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

    public function cancelSubscription(string $reference)
    {
        // If it’s a Checkout Session ID (starts with cs_)
        if (str_starts_with($reference, 'cs_')) {
            $session = $this->stripe->checkout->sessions->retrieve($reference);
            if (!empty($session->subscription)) {
                $this->stripe->subscriptions->update($session->subscription, [
                    'cancel_at_period_end' => true, // set to false for immediate cancel
                ]);
            }
        }

        // If it’s a Subscription ID (starts with sub_)
        elseif (str_starts_with($reference, 'sub_')) {
            $this->stripe->subscriptions->update($reference, [
                'cancel_at_period_end' => true,
            ]);
        }

        return true;
    }
}
