<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\User;
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
}
