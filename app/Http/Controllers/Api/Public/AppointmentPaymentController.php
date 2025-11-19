<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class AppointmentPaymentController extends Controller
{
    protected function paypalClient()
    {
        return new PayPalHttpClient(
            new SandboxEnvironment(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            )
        );
    }

    // ---------------------------
    // STRIPE CHECKOUT
    // ---------------------------
    public function stripe(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,Id',
            'account_id' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        $appointment = Appointment::find($request->appointment_id);

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $appointment->customer->Email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'gbp',
                    'product_data' => [
                        'name' => $appointment->service->Name,
                    ],
                    'unit_amount' => $request->amount * 100,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'appointment_id' => $appointment->Id,
            ],
            'success_url' => url("/public/payment/success/{$appointment->Id}"),
            'cancel_url' => url("/public/payment/cancel/{$appointment->Id}"),
        ]);

        return response()->json(['url' => $session->url]);
    }

    // ---------------------------
    // PAYPAL CHECKOUT
    // ---------------------------
    public function paypal(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,Id',
            'account_id' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = number_format($request->amount, 2, '.', '');

        $order = new OrdersCreateRequest();
        $order->prefer('return=representation');
        $order->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'GBP',
                    'value' => $amount,
                ]
            ]],
            'application_context' => [
                'return_url' => url("/public/payment/paypal/success/{$request->appointment_id}"),
                'cancel_url' => url("/public/payment/paypal/cancel/{$request->appointment_id}"),
            ]
        ];

        $client = $this->paypalClient();
        $response = $client->execute($order);

        return response()->json([
            'approval_url' => $response->result->links[1]->href,
        ]);
    }
}
