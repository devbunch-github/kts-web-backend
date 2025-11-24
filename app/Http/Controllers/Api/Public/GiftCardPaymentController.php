<?php

// app/Http/Controllers/Api/Public/GiftCardPaymentController.php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GiftCardPurchase;
use App\Models\GiftCard;
use App\Models\Customer;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GiftCardPaymentController extends Controller
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

    /**
     * Create / redirect Stripe Checkout for a gift card purchase
     */
    public function stripe(Request $request)
    {
        $validated = $request->validate([
            // adjust table/column if your GiftCards table is named differently
            'gift_card_id' => 'required|exists:gift_cards,Id',
            'customer_id'  => 'required|exists:Customers,Id',
            'account_id'   => 'required|integer',
            'amount'       => 'required|numeric|min:1',
            'subdomain'    => 'required|string',
        ]);

        $giftCard  = GiftCard::findOrFail($validated['gift_card_id']);
        $customer  = Customer::findOrFail(5748);

        // Create purchase row first
        $purchase = GiftCardPurchase::create([
            'GiftCardId'    => $giftCard->id,
            'CustomerId'    => $customer->Id,
            'AccountId'     => $validated['account_id'],
            'Code'          => 'GC-' . strtoupper(Str::random(10)),
            'Amount'        => $validated['amount'],
            'PaymentMethod' => 'stripe',
            'PaymentStatus' => 'pending',
            'ExpiresAt'     => Carbon::now()->addYear(), // 1 year validity
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        $frontend   = config('app.frontend_url', env('FRONTEND_URL'));
        $successUrl = "{$frontend}/{$validated['subdomain']}/gift-card/payment/success/{$purchase->Id}";
        $cancelUrl  = "{$frontend}/{$validated['subdomain']}/gift-card/payment/cancel/{$purchase->Id}";

        $session = StripeSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $customer->Email ?? null,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'gbp',
                    'product_data' => [
                        'name' => $giftCard->Title ?? $giftCard->Name ?? 'Gift Card',
                    ],
                    'unit_amount' => $validated['amount'] * 100,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'gift_card_purchase_id' => $purchase->Id,
                'gift_card_id'          => $giftCard->Id,
            ],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

        $purchase->StripeSessionId = $session->id;
        $purchase->save();

        return response()->json(['url' => $session->url]);
    }

    /**
     * Create PayPal order for a gift card purchase
     */
    public function paypal(Request $request)
    {
        $validated = $request->validate([
            'gift_card_id' => 'required|exists:gift_cards,Id',
            'customer_id'  => 'required|exists:Customers,Id',
            'account_id'   => 'required|integer',
            'amount'       => 'required|numeric|min:1',
            'subdomain'    => 'required|string',
        ]);

        $giftCard = GiftCard::findOrFail($validated['gift_card_id']);
        $customer = Customer::findOrFail($validated['customer_id']);

        // Create purchase row first
        $purchase = GiftCardPurchase::create([
            'GiftCardId'    => $giftCard->Id,
            'CustomerId'    => $customer->Id,
            'AccountId'     => $validated['account_id'],
            'Code'          => 'GC-' . strtoupper(Str::random(10)),
            'Amount'        => $validated['amount'],
            'PaymentMethod' => 'paypal',
            'PaymentStatus' => 'pending',
            'ExpiresAt'     => Carbon::now()->addYear(),
        ]);

        $amount = number_format($validated['amount'], 2, '.', '');

        $frontend   = config('app.frontend_url', env('FRONTEND_URL'));
        $successUrl = "{$frontend}/{$validated['subdomain']}/gift-card/payment/paypal/success/{$purchase->Id}";
        $cancelUrl  = "{$frontend}/{$validated['subdomain']}/gift-card/payment/paypal/cancel/{$purchase->Id}";

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

        $client   = $this->paypalClient();
        $response = $client->execute($order);

        $approvalUrl = collect($response->result->links ?? [])
            ->firstWhere('rel', 'approve')->href ?? null;

        $purchase->PayPalOrderId = $response->result->id ?? null;
        $purchase->save();

        return response()->json([
            'approval_url' => $approvalUrl,
        ]);
    }

    /**
     * Mark purchase as paid – called by your front-end success page
     * e.g. /api/public/gift-cards/purchase/{id}/mark-paid
     */
    public function markAsPaid(Request $request, $purchaseId)
    {
        $purchase = GiftCardPurchase::find($purchaseId);

        if (!$purchase) {
            return response()->json([
                'success' => false,
                'message' => 'Gift card purchase not found',
            ], 404);
        }

        $purchase->PaymentStatus = 'paid';
        $purchase->PaidAt        = now();
        $purchase->save();

        return response()->json(['success' => true]);
    }
}
