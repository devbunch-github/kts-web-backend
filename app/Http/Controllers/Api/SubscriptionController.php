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

}