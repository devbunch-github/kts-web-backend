<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class PayPalService
{
    protected $client;
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->client = Http::asJson();
    }

    protected function token()
    {
        $res = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        return $res['access_token'];
    }

    public function createSubscription(Plan $plan, User $user)
    {
        $token = $this->token();

        $res = $this->client->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/v1/billing/subscriptions", [
            'plan_id' => $plan->paypal_plan_id,
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/subscription/set-password?user_id=' . $user->id,
                'cancel_url' => env('FRONTEND_URL', 'http://localhost:5173') . '/payment-cancelled?user_id=' . $user->id,
            ],
            'subscriber' => [
                'email_address' => $user->email,
                'name' => [
                    'given_name' => $user->first_name ?? '',
                    'surname' => $user->last_name ?? '',
                ],
            ],
        ]);

        return json_decode($res->body(), true);
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $token = $this->token();

        $response = $this->client->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/v1/billing/subscriptions/{$subscriptionId}/cancel", [
            'reason' => 'Cancelled by admin via backend',
        ]);

        if (!$response->successful()) {
            throw new \Exception('PayPal cancellation failed: ' . $response->body());
        }

        return true;
    }

}
