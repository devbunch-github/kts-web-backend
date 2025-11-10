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

        $session = $this->stripe->createSubscriptionSession($plan, $user);

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

    /**
     * Upgrade (or change) the user’s current subscription plan.
     * Cancels old subscription remotely (Stripe/PayPal) and locally,
     * then creates a new checkout session for the selected plan.
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id'  => 'required|exists:plans,id',
            'user_id'  => 'required|exists:users,id',
            'provider' => 'required|in:stripe,paypal',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $user = User::findOrFail($request->user_id);

        // 🔹 STEP 1: Cancel any existing active subscription (remote + local)
        $existingSubs = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->get();

        foreach ($existingSubs as $sub) {
            try {
                // ✅ Stripe cancellation (remote)
                if ($sub->payment_provider === 'stripe' && $sub->payment_reference) {
                    try {
                        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

                        // Retrieve the checkout session to find its subscription
                        if (str_starts_with($sub->payment_reference, 'cs_')) {
                            $session = $stripe->checkout->sessions->retrieve($sub->payment_reference);

                            if (!empty($session->subscription)) {
                                // ✅ Cancel the actual subscription immediately
                                $stripe->subscriptions->cancel($session->subscription);
                            } else {
                                \Log::warning("Stripe session {$sub->payment_reference} has no subscription attached.");
                            }
                        }

                        // If the payment_reference itself is a sub_ ID (rare but possible)
                        elseif (str_starts_with($sub->payment_reference, 'sub_')) {
                            $stripe->subscriptions->cancel($sub->payment_reference);
                        }

                        // ✅ Mark local record as cancelled
                        $sub->update(['status' => 'cancelled', 'ends_at' => now()]);

                    } catch (\Throwable $e) {
                        \Log::warning("Stripe cancel failed [{$sub->id}]: " . $e->getMessage());
                    }
                }



                // ✅ PayPal cancellation (remote)
                if ($sub->payment_provider === 'paypal' && $sub->payment_reference) {
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
                        'json' => ['reason' => 'User upgraded or changed plan.'],
                    ]);
                }

                // ✅ Mark local record as cancelled
                $sub->update(['status' => 'cancelled', 'ends_at' => now()]);

            } catch (\Throwable $e) {
                \Log::warning("Subscription cancel failed [{$sub->id}]: " . $e->getMessage());
            }
        }

        // 🔹 STEP 2: Create new checkout for the selected provider
        if ($request->provider === 'stripe') {
            $session = $this->stripe->createSubscriptionSession($plan, $user);

            $this->subs->create([
                'user_id'          => $user->id,
                'plan_id'          => $plan->id,
                'status'           => 'pending',
                'payment_provider' => 'stripe',
                'payment_reference'=> $session->id,
            ]);

            return response()->json(['checkoutUrl' => $session->url]);
        }

        if ($request->provider === 'paypal') {
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

        return response()->json(['message' => 'Invalid provider'], 400);
    }

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