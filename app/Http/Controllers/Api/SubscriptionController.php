<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use App\Repositories\Eloquent\SubscriptionRepository;
use App\Services\StripeService;
use App\Services\PayPalService;

class SubscriptionController extends Controller
{
    protected $subs, $stripe, $paypal;

    public function __construct(
        SubscriptionRepository $subs,
        StripeService $stripe,
        PayPalService $paypal
    ) {
        $this->subs = $subs;
        $this->stripe = $stripe;
        $this->paypal = $paypal;
    }

    /** ---------------- Stripe Subscription ---------------- */
    public function createStripe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = User::findOrFail($request->user_id);

        $isSignupFlow = request()->boolean('is_signup', false);
        $session = $this->stripe->createSubscriptionSession($plan, $user, $isSignupFlow);

        $this->subs->create([
            'user_id'          => $user->id,
            'plan_id'          => $plan->id,
            'status'           => 'pending',
            'payment_provider' => 'stripe',
            'payment_reference'=> $session->id,
        ]);

        return response()->json([
            'checkoutUrl' => $session->url,
        ]);

    }

    /** ---------------- PayPal Subscription ---------------- */
    public function createPayPal(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = User::findOrFail($request->user_id);

        $res = $this->paypal->createSubscription($plan, $user);

        $this->subs->create([
            'user_id'          => $user->id,
            'plan_id'          => $plan->id,
            'status'           => 'pending',
            'payment_provider' => 'paypal',
            'payment_reference'=> $res['id'],
        ]);

        return response()->json(['approvalUrl' => $res['links'][0]['href']]);
    }

    public function getSubscriptions()
    {
        $subscriptions = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'uid' => $sub->user?->id ?? 0,
                    'name' => $sub->user?->name ?? 'N/A',
                    'email' => $sub->user?->email ?? 'N/A',
                    'subscription' => $sub->plan?->name ?? 'N/A',
                    'payment_provider' => $sub->payment_provider ?? 'N/A',
                    'payment_reference' => $sub->payment_reference ?? 'N/A',
                    'status' => ucfirst($sub->status ?? 'unknown'),
                    'starts_at' => $sub->starts_at ? $sub->starts_at->format('Y-m-d') : null,
                    'ends_at' => $sub->ends_at ? $sub->ends_at->format('Y-m-d') : null,
                    'created_at' => $sub->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    public function cancel($id)
    {
        $sub = Subscription::find($id);
        if (!$sub) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        try {
            if ($sub->payment_provider === 'stripe') {
                $this->stripe->cancelSubscription($sub->payment_reference);
            }

            if ($sub->payment_provider === 'paypal') {
                $this->paypal->cancelSubscription($sub->payment_reference);
            }

            $sub->status = 'cancelled';
            $sub->ends_at = now();
            $sub->save();

            return response()->json(['message' => 'Subscription cancelled successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cancellation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function myActive(Request $request)
    {
        $sub = Subscription::with('plan')
            ->where('user_id', auth()->user()->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        return response()->json(['data' => $sub]);
    }

    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id'  => 'required|exists:plans,id',
            'user_id'  => 'required|exists:users,id',
            'provider' => 'required|in:stripe,paypal',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = User::findOrFail($request->user_id);

        // ✅ Get active subscription
        $currentSub = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$currentSub) {
            // No existing → create new
            return $this->createStripe($request);
        }

        try {
            if ($request->provider === 'stripe') {
                // ⚡ Use built-in prorated upgrade
                $updatedStripeSub = $this->stripe->upgradeSubscription(
                    $currentSub->payment_reference,
                    $plan
                );

                $currentSub->update([
                    'plan_id'  => $plan->id,
                    'status'   => 'active',
                    'updated_at' => now(),
                    'payment_reference' => $updatedStripeSub->id ?? $currentSub->payment_reference,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription upgraded with prorated billing.',
                ]);
            }

            if ($request->provider === 'paypal') {

                // Calculate remaining days & pro-rated charge
                $daysLeft = 0;
                $totalDays = 30; // default to 30 if unknown

                if ($currentSub->starts_at && $currentSub->ends_at) {
                    $totalDays = $currentSub->starts_at->diffInDays($currentSub->ends_at);
                    $remainingDays = now()->diffInDays($currentSub->ends_at, false);
                    $daysLeft = max($remainingDays, 0);
                }

                // Price of old/new plan
                $oldPrice = $currentSub->plan ? ($currentSub->plan->price_minor / 100) : 0;
                $newPrice = $plan->price_minor / 100;

                // Credit for unused old plan
                $unusedCredit = $daysLeft > 0 ? round(($daysLeft / $totalDays) * $oldPrice, 2) : 0;

                // Charge for remaining days of new plan
                $remainingCharge = $daysLeft > 0 ? round(($daysLeft / $totalDays) * $newPrice, 2) : $newPrice;

                // Final first-cycle charge (new plan - unused old)
                $firstCycleCharge = max($remainingCharge - $unusedCredit, 0);

                // Cancel existing subscription remotely
                try {
                    $this->paypal->cancelSubscription($currentSub->payment_reference);
                } catch (\Throwable $e) {
                    \Log::warning("PayPal cancel warning: " . $e->getMessage());
                }

                $currentSub->update(['status' => 'cancelled', 'ends_at' => now()]);

                // Create new PayPal subscription with overridden price for the first cycle
                $res = $this->paypal->createProratedSubscription($plan, $user, $firstCycleCharge);

                $this->subs->create([
                    'user_id'           => $user->id,
                    'plan_id'           => $plan->id,
                    'status'            => 'pending',
                    'payment_provider'  => 'paypal',
                    'payment_reference' => $res['id'],
                ]);

                return response()->json(['approvalUrl' => $res['links'][0]['href']]);
            }

            return response()->json(['message' => 'Invalid provider.'], 400);

        } catch (\Throwable $e) {
            \Log::error("Upgrade failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upgrade failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Upgrade (or change) the user’s current subscription plan.
     * Cancels old subscription remotely (Stripe/PayPal) and locally,
     * then creates a new checkout session for the selected plan.
     */
    // public function upgrade(Request $request)
    // {
    //     $request->validate([
    //         'plan_id'  => 'required|exists:plans,id',
    //         'user_id'  => 'required|exists:users,id',
    //         'provider' => 'required|in:stripe,paypal',
    //     ]);

    //     $plan = Plan::findOrFail($request->plan_id);
    //     $user = User::findOrFail($request->user_id);

    //     // Fetch user's current active subscription
    //     $currentSub = Subscription::where('user_id', $user->id)
    //         ->where('status', 'active')
    //         ->latest()
    //         ->first();

    //     if (!$currentSub) {
    //         // No existing → normal checkout
    //         return $this->createNewSubscription($request, $plan, $user);
    //     }

    //     if ($request->provider === 'stripe') {
    //         try {
    //             // ✅ Stripe prorated upgrade
    //             $updated = $this->stripe->upgradeSubscription($currentSub->payment_reference, $plan);

    //             $currentSub->update([
    //                 'plan_id' => $plan->id,
    //                 'status'  => 'active',
    //                 'updated_at' => now(),
    //             ]);

    //             return response()->json(['message' => 'Subscription upgraded with fair billing.']);
    //         } catch (\Throwable $e) {
    //             \Log::error("Stripe upgrade failed: " . $e->getMessage());
    //             return response()->json(['message' => 'Upgrade failed', 'error' => $e->getMessage()], 500);
    //         }
    //     }

    //     if ($request->provider === 'paypal') {
    //         // ❗ PayPal does not support prorations natively, so simulate discount
    //         $daysLeft = 0;
    //         if ($currentSub->ends_at) {
    //             $total = $currentSub->starts_at->diffInDays($currentSub->ends_at);
    //             $remaining = now()->diffInDays($currentSub->ends_at, false);
    //             $daysLeft = max($remaining, 0);
    //             $discount = round(($daysLeft / $total) * ($currentSub->plan->price_minor / 100), 2);
    //         } else {
    //             $discount = 0;
    //         }

    //         // Cancel old PayPal sub
    //         try {
    //             $this->paypal->cancelSubscription($currentSub->payment_reference);
    //         } catch (\Throwable $e) {
    //             \Log::warning("PayPal cancel warning: " . $e->getMessage());
    //         }
    //         $currentSub->update(['status' => 'cancelled', 'ends_at' => now()]);

    //         // Create new PayPal sub with discount
    //         $res = $this->paypal->createSubscriptionWithDiscount($plan, $user, $discount);

    //         $this->subs->create([
    //             'user_id'          => $user->id,
    //             'plan_id'          => $plan->id,
    //             'status'           => 'pending',
    //             'payment_provider' => 'paypal',
    //             'payment_reference'=> $res['id'],
    //         ]);

    //         return response()->json(['approvalUrl' => $res['links'][0]['href']]);
    //     }

    //     return response()->json(['message' => 'Invalid provider'], 400);
    // }


    public function cancelSubscription()
    {
        $user = auth()->user();
        $sub = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$sub) {
            return response()->json(['message' => 'No active subscription found.'], 404);
        }

        try {
            if ($sub->payment_provider === 'stripe') {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                if (str_starts_with($sub->payment_reference, 'cs_')) {
                    $session = $stripe->checkout->sessions->retrieve($sub->payment_reference);
                    if (!empty($session->subscription)) {
                        $stripe->subscriptions->cancel($session->subscription);
                    }
                } elseif (str_starts_with($sub->payment_reference, 'sub_')) {
                    $stripe->subscriptions->cancel($sub->payment_reference);
                }
            }

            if ($sub->payment_provider === 'paypal') {
                $paypal = new \GuzzleHttp\Client(['base_uri' => 'https://api-m.sandbox.paypal.com/']);
                $paypalToken = json_decode($paypal->post('v1/oauth2/token', [
                    'auth'        => [config('services.paypal.client_id'), config('services.paypal.secret')],
                    'form_params' => ['grant_type' => 'client_credentials'],
                ])->getBody(), true)['access_token'];

                $paypal->post("v1/billing/subscriptions/{$sub->payment_reference}/cancel", [
                    'headers' => [
                        'Authorization' => "Bearer $paypalToken",
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => ['reason' => 'User cancelled the subscription.'],
                ]);
            }

            $sub->update(['status' => 'cancelled', 'ends_at' => now()]);

            return response()->json(['message' => 'Subscription cancelled successfully.']);
        } catch (\Throwable $e) {
            \Log::warning('Subscription cancel failed: ' . $e->getMessage());
            return response()->json(['message' => 'Cancellation failed.'], 500);
        }
    }

}