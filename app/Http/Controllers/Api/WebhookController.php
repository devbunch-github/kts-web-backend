<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Stripe;

class WebhookController extends Controller
{
    public function handleStripe(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $payload = $request->getContent();

        try {
            $event = json_decode($payload, true);
            $type = $event['type'] ?? null;

            if (in_array($type, ['checkout.session.completed', 'invoice.payment_succeeded'])) {
                $session = $event['data']['object'];
                $reference = $session['id'] ?? $session['subscription'] ?? null;

                if ($reference) {
                    Subscription::where('payment_reference', $reference)
                        ->update(['status' => 'active']);
                }
            }
        } catch (\Exception $e) {
            Log::error("Stripe webhook error: ".$e->getMessage());
        }

        return response('ok', 200);
    }

    public function handlePayPal(Request $request)
    {
        $event = $request->all();
        Log::info('PayPal Webhook', $event);

        if (($event['event_type'] ?? null) === 'BILLING.SUBSCRIPTION.ACTIVATED') {
            $id = $event['resource']['id'] ?? null;
            if ($id) {
                Subscription::where('payment_reference', $id)
                    ->update(['status' => 'active']);
            }
        }

        if (($event['event_type'] ?? null) === 'BILLING.SUBSCRIPTION.CANCELLED') {
            $id = $event['resource']['id'] ?? null;
            if ($id) {
                Subscription::where('payment_reference', $id)
                    ->update(['status' => 'cancelled']);
            }
        }

        return response('ok', 200);
    }
}
