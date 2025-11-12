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

    public function createSubscriptionSession(Plan $plan, User $user, bool $isSignup = false)
    {
        $redirectBase = env('FRONTEND_URL');

        if ($isSignup) {
            // 🟢 New signup flow
            $successUrl = "{$redirectBase}/subscription/set-password?user_id={$user->id}&session_id={CHECKOUT_SESSION_ID}";
            $cancelUrl  = "{$redirectBase}/payment-cancelled?user_id={$user->id}";
        } else {
            // 🔵 Existing user upgrading
            $successUrl = "{$redirectBase}/dashboard/subscription?success=true";
            $cancelUrl  = "{$redirectBase}/dashboard/subscription?cancelled=true";
        }

        return $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer_email' => $user->email,
            'line_items' => [[
                'price' => $plan->stripe_plan_id,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

    }

    // public function createSubscriptionSession(Plan $plan, Useutr $user)
    // {
    //     return $this->stripe->checkout->sessions->create([
    //         'mode' => 'subscription',
    //         'customer_email' => $user->email,
    //         'line_items' => [[
    //             'price' => $plan->stripe_plan_id,
    //             'quantity' => 1,
    //         ]],
    //         'success_url' => env('FRONTEND_URL') . '/subscription/set-password?user_id=' . $user->id . '&session_id={CHECKOUT_SESSION_ID}',
    //         'cancel_url'  => env('FRONTEND_URL') . '/payment-cancelled?user_id=' . $user->id,
    //     ]);

    // }

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

    public function upgradeSubscription($oldSubReference, Plan $newPlan)
    {
        $stripe = $this->stripe;
        $subscriptionId = null;

        // 1️⃣ Get existing subscription ID
        if (str_starts_with($oldSubReference, 'cs_')) {
            $session = $stripe->checkout->sessions->retrieve($oldSubReference);
            $subscriptionId = $session->subscription ?? null;
        } elseif (str_starts_with($oldSubReference, 'sub_')) {
            $subscriptionId = $oldSubReference;
        }

        if (!$subscriptionId) {
            throw new \Exception("Existing Stripe subscription not found.");
        }

        // 2️⃣ Update subscription plan with prorations
        $existing = $stripe->subscriptions->retrieve($subscriptionId);
        $updated = $stripe->subscriptions->update($subscriptionId, [
            'items' => [[
                'id'    => $existing->items->data[0]->id,
                'price' => $newPlan->stripe_plan_id,
            ]],
            'proration_behavior' => 'create_prorations',
        ]);

        // 3️⃣ Force Stripe to immediately generate + charge proration invoice
        $invoice = $stripe->invoices->create([
            'customer' => $updated->customer,
            'subscription' => $updated->id,
            'description' => 'Immediate proration charge for plan upgrade',
        ]);
        $stripe->invoices->finalizeInvoice($invoice->id);
        $stripe->invoices->pay($invoice->id);

        return $updated;
    }

}
