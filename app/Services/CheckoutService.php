<?php

namespace App\Services;

use App\Models\Subscription;

class CheckoutService
{
    // If you later add a PaymentRepositoryInterface, inject it here.
    public function createIntent(int $planId, ?string $email = null)
    {
        // Stub: replace with Stripe/PSP call
        return response()->json(['client_secret' => 'demo_secret_'.$planId]);
    }

    public function confirm(int $planId, int $userId)
    {
        $sub = Subscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'status'  => 'active',
            'starts_at' => now(),
        ]);
        return response()->json(['ok'=>true,'subscription_id'=>$sub->id]);
    }
}
