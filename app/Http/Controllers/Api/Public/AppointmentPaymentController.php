<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\GiftCardPurchase;
use App\Models\GiftCardUsage;
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

    public function stripe(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,Id',
            'account_id'     => 'required',
            'amount'         => 'required|numeric|min:1',
            'subdomain'      => 'required|string'
        ]);

        $appointment = Appointment::find($request->appointment_id);

        Stripe::setApiKey(config('services.stripe.secret'));

        $frontend   = config('app.frontend_url', env('FRONTEND_URL'));
        $successUrl = "{$frontend}/{$request->subdomain}/payment/success/{$appointment->Id}";
        $cancelUrl  = "{$frontend}/{$request->subdomain}/payment/cancel/{$appointment->Id}";

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
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function paypal(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,Id',
            'account_id'     => 'required',
            'amount'         => 'required|numeric|min:1',
            'subdomain'      => 'required|string'
        ]);

        $amount = number_format($request->amount, 2, '.', '');

        $frontend   = config('app.frontend_url', env('FRONTEND_URL'));
        $successUrl = "{$frontend}/{$request->subdomain}/payment/paypal/success/{$request->appointment_id}";
        $cancelUrl  = "{$frontend}/{$request->subdomain}/payment/paypal/cancel/{$request->appointment_id}";

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
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]
        ];

        $client = $this->paypalClient();
        $response = $client->execute($order);

        return response()->json([
            'approval_url' => $response->result->links[1]->href,
        ]);
    }

    /**
     * Called from PaymentSuccessPage after Stripe/PayPal redirect
     * Route: POST /api/public/payment/mark-paid/{appointmentId}
     */
    public function markAsPaid(Request $request, $appointmentId)
    {
        $appointment = Appointment::find($appointmentId);
        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'Appointment not found'], 404);
        }

        // 1) Mark as paid
        $appointment->Status = 1;
        $appointment->save();

        // 2) Track PROMO usage (if any)
        if (!empty($appointment->PromoCode) && $appointment->Discount > 0) {
            $promo = PromoCode::where('code', $appointment->PromoCode)
                ->where('account_id', $appointment->AccountId)
                ->first();

            if ($promo) {
                PromoCodeUsage::create([
                    'promo_code_id'  => $promo->id,
                    'customer_id'    => $appointment->CustomerId,
                    'appointment_id' => $appointment->Id,
                    'account_id'     => $appointment->AccountId,
                    'used_amount'    => $appointment->Discount,
                ]);
            }
        }

        // 3) Track GIFT CARD usage (if any)
        if (!empty($appointment->GiftCardCode) && $appointment->GiftCardAmount > 0) {
            $purchase = GiftCardPurchase::where('Code', $appointment->GiftCardCode)
                ->where('AccountId', $appointment->AccountId)
                ->first();

            if ($purchase) {
                GiftCardUsage::create([
                    'gift_card_purchase_id' => $purchase->Id,
                    'customer_id'           => $appointment->CustomerId,
                    'appointment_id'        => $appointment->Id,
                    'account_id'            => $appointment->AccountId,
                    'used_amount'           => $appointment->GiftCardAmount,
                ]);

                $purchase->UsedAmount = $purchase->UsedAmount + $appointment->GiftCardAmount;
                $purchase->save();
            }
        }

        return response()->json(['success' => true]);
    }
}
