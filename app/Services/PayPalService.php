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

        // 🧭 Detect if user is already logged in (dashboard upgrade)
        $redirectBase = env('FRONTEND_URL', 'http://localhost:5173');
        $isBusinessUser = $user->hasRole('business_admin') || $user->hasRole('business');

        $returnUrl = $isBusinessUser
            ? "{$redirectBase}/dashboard/subscription?success=true"
            : "{$redirectBase}/subscription/set-password?user_id={$user->id}";

        $cancelUrl = $isBusinessUser
            ? "{$redirectBase}/dashboard/subscription?cancelled=true"
            : "{$redirectBase}/payment-cancelled?user_id={$user->id}";

        $res = $this->client->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/v1/billing/subscriptions", [
            'plan_id' => $plan->paypal_plan_id,
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                // ✅ Use dynamic return URLs based on user context
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
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

    public function createSubscriptionWithDiscount(Plan $plan, User $user, float $discountAmount = 0)
    {
        $token = $this->token();
        $redirectBase = env('FRONTEND_URL', 'http://localhost:5173');
        $isBusinessUser = $user->hasRole('business_admin') || $user->hasRole('business');

        $returnUrl = $isBusinessUser
            ? "{$redirectBase}/dashboard/subscription?success=true"
            : "{$redirectBase}/subscription/set-password?user_id={$user->id}";
        $cancelUrl = $isBusinessUser
            ? "{$redirectBase}/dashboard/subscription?cancelled=true"
            : "{$redirectBase}/payment-cancelled?user_id={$user->id}";

        // Apply manual discount by lowering first payment
        $price = max($plan->price_minor / 100 - $discountAmount, 0);

        $res = $this->client->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/v1/billing/subscriptions", [
            'plan_id' => $plan->paypal_plan_id,
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
            'plan_overridden' => true,
            'billing_cycles' => [[
                'frequency' => ['interval_unit' => 'MONTH', 'interval_count' => 1],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0,
                'pricing_scheme' => [
                    'fixed_price' => [
                        'value' => number_format($price, 2, '.', ''),
                        'currency_code' => 'GBP',
                    ],
                ],
            ]],
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

    public function createProratedSubscription(Plan $plan, User $user, float $firstCycleCharge)
    {
        $token = $this->token();

        $redirectBase = env('FRONTEND_URL', 'http://localhost:5173');
        $isBusinessUser = $user->hasRole('business_admin') || $user->hasRole('business');

        $returnUrl = $isBusinessUser
            ? "{$redirectBase}/dashboard/subscription?success=true"
            : "{$redirectBase}/subscription/set-password?user_id={$user->id}";
        $cancelUrl = $isBusinessUser
            ? "{$redirectBase}/dashboard/subscription?cancelled=true"
            : "{$redirectBase}/payment-cancelled?user_id={$user->id}";

        // Override first cycle price for prorated charge
        $res = $this->client->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->post("{$this->baseUrl}/v1/billing/subscriptions", [
            'plan_id' => $plan->paypal_plan_id,
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
            'plan_overridden' => true,
            'billing_cycles' => [[
                'frequency' => ['interval_unit' => 'MONTH', 'interval_count' => 1],
                'tenure_type' => 'REGULAR',
                'sequence' => 1,
                'total_cycles' => 0,
                'pricing_scheme' => [
                    'fixed_price' => [
                        'value' => number_format($firstCycleCharge, 2, '.', ''),
                        'currency_code' => 'USD', // or GBP if your PayPal plan currency is GBP
                    ],
                ],
            ]],
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



}
