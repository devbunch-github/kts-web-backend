<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Subscription;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

class PaymentController extends Controller
{
    protected function paypalClient()
    {
        $mode = config('services.paypal.mode', 'sandbox');
        $env = $mode === 'live'
            ? new ProductionEnvironment(config('services.paypal.client_id'), config('services.paypal.secret'))
            : new SandboxEnvironment(config('services.paypal.client_id'), config('services.paypal.secret'));
        return new PayPalHttpClient($env);
    }

    /** ---------------- STRIPE ---------------- */
    public function createStripeIntent(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $amount = $plan->price_minor;

        if ($amount < 50) {
            return response()->json(['error' => 'Plan must be at least $0.50'], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            'metadata' => [
                'plan_id' => $plan->id,
                'user_id' => $request->user_id,
            ]
        ]);

        Subscription::create([
            'user_id' => $request->user_id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'payment_provider' => 'stripe',
            'payment_reference' => $intent->id,
        ]);

        return response()->json([
            'client_secret' => $intent->client_secret,
        ]);
    }

    /** ---------------- PAYPAL ---------------- */
    public function createPayPalOrder(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $amount = number_format($plan->price_minor / 100, 2, '.', '');

        $order = new OrdersCreateRequest();
        $order->prefer('return=representation');
        $order->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => $amount,
                ]
            ]]
        ];

        $client = $this->paypalClient();
        $response = $client->execute($order);

        Subscription::create([
            'user_id' => $request->user_id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'payment_provider' => 'paypal',
            'payment_reference' => $response->result->id,
        ]);

        return response()->json(['id' => $response->result->id]);
    }

    public function capturePayPalOrder(Request $request)
    {
        $client = $this->paypalClient();
        $capture = $client->execute(new OrdersCaptureRequest($request->order_id));

        $sub = Subscription::where('payment_reference', $request->order_id)->firstOrFail();
        $sub->status = 'active';
        $sub->save();

        return response()->json(['success' => true]);
    }

    /** ---------------- GENERIC CONFIRM ---------------- */
    public function confirmPayment(Request $request)
    {
        $sub = Subscription::where('payment_reference', $request->payment_id)->first();
        if ($sub) {
            $sub->status = 'active';
            $sub->save();
        }
        return response()->json(['success' => true]);
    }
}
