<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PlanService;
use App\Http\Resources\PlanResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use App\Models\Plan;
use App\Models\Subscription;

class PlanController extends Controller {
    public function __construct(private PlanService $plans) {}

    public function index()
    {
        return PlanResource::collection($this->plans->active());
    }

    /**
     * Return single plan by ID
     */
    public function show($id)
    {
        $plan = $this->plans->find($id);

        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new PlanResource($plan);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration' => 'required|in:weekly,monthly,yearly',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|string',
        ]);

        $features = array_map('trim', explode(',', $validated['features'] ?? ''));
        $price_minor = (int) round($validated['price'] * 100);

        try {
            // ===== STRIPE =====
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Check if product already exists (by name)
            $existingProducts = \Stripe\Product::all(['limit' => 100]);
            $existing = collect($existingProducts->data)->firstWhere('name', $validated['name']);

            $stripeProduct = $existing ?: \Stripe\Product::create([
                'name' => $validated['name'],
            ]);

            $interval = match ($validated['duration']) {
                'yearly' => 'year',
                'weekly' => 'week',
                default => 'month'
            };

            $stripePrice = \Stripe\Price::create([
                'unit_amount' => $price_minor,
                'currency' => 'gbp',
                'recurring' => ['interval' => $interval],
                'product' => $stripeProduct->id,
            ]);

            // ===== PAYPAL =====
            $verify = app()->environment('local') ? false : true; // bypass SSL on local

            $paypal = new \GuzzleHttp\Client([
                'base_uri' => 'https://api-m.sandbox.paypal.com/',
                'verify' => $verify,
            ]);

            // Get OAuth Token
            $paypalTokenResponse = $paypal->post('v1/oauth2/token', [
                'auth' => [config('services.paypal.client_id'), config('services.paypal.secret')],
                'form_params' => ['grant_type' => 'client_credentials'],
            ]);
            $paypalToken = json_decode($paypalTokenResponse->getBody(), true)['access_token'];

            // Existing PayPal product ID
            $paypalProductId = config('services.paypal.product_id');

            // Determine interval unit
            $interval = match ($validated['duration']) {
                'yearly' => 'YEAR',
                'weekly' => 'WEEK',
                default => 'MONTH',
            };

            // Create a new billing plan under the existing product
            $paypalPlanResponse = $paypal->post('v1/billing/plans', [
                'headers' => [
                    'Authorization' => "Bearer $paypalToken",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'product_id' => $paypalProductId,
                    'name' => $validated['name'],
                    'description' => 'Auto-created subscription plan for ' . $validated['name'],
                    'billing_cycles' => [[
                        'frequency' => [
                            'interval_unit' => $interval,
                            'interval_count' => 1,
                        ],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0,
                        'pricing_scheme' => [
                            'fixed_price' => [
                                'value' => number_format($validated['price'], 2, '.', ''),
                                'currency_code' => 'GBP',
                            ],
                        ],
                    ]],
                    'payment_preferences' => [
                        'auto_bill_outstanding' => true,
                        'setup_fee_failure_action' => 'CONTINUE',
                        'payment_failure_threshold' => 1,
                    ],
                ],
            ]);

            $paypalPlan = json_decode($paypalPlanResponse->getBody(), true);

            // ===== SAVE TO DB =====
            $plan = Plan::create([
                'name' => $validated['name'],
                'price_minor' => $price_minor,
                'currency' => 'GBP',
                'duration' => $validated['duration'],
                'features' => $features,
                'stripe_plan_id' => $stripePrice->id,
                'paypal_plan_id' => $paypalPlan['id'],
                'is_active' => true,
            ]);

            return response()->json([
                'message' => 'Plan created successfully.',
                'data' => $plan,
            ], 201);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Stripe error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Stripe error: ' . $e->getMessage(),
            ], 400);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $error = $e->getResponse()?->getBody()?->getContents() ?? $e->getMessage();
            \Log::error('PayPal API error: ' . $error);
            return response()->json([
                'message' => 'PayPal error: ' . $error,
            ], 400);
        } catch (\Throwable $e) {
            \Log::error('Plan creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::find($id);
        if (!$plan) {
            return response()->json(['message' => 'Plan not found.'], 404);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'duration' => 'required|in:weekly,monthly,yearly',
            'price'    => 'required|numeric|min:0',
            'features' => 'nullable|string',
        ]);

        $features    = array_map('trim', explode(',', $validated['features'] ?? ''));
        $priceMinor  = (int) round($validated['price'] * 100);
        $durationOld = $plan->duration;
        $priceOld    = (int) $plan->price_minor;

        try {
            // ===== STRIPE =====
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Resolve current Stripe product via the existing price
            $existingPrice = \Stripe\Price::retrieve($plan->stripe_plan_id);
            $stripeProductId = is_string($existingPrice->product) ? $existingPrice->product : $existingPrice->product->id;

            // Update product name (allowed)
            \Stripe\Product::update($stripeProductId, ['name' => $validated['name']]);

            $stripeInterval = match ($validated['duration']) {
                'yearly' => 'year',
                'weekly' => 'week',
                default  => 'month',
            };

            $needNewStripePrice = ($priceMinor !== $priceOld) || ($validated['duration'] !== $durationOld);

            if ($needNewStripePrice) {
                // Deactivate old price
                \Stripe\Price::update($plan->stripe_plan_id, ['active' => false]);

                // Create new price with updated amount/interval
                $newStripePrice = \Stripe\Price::create([
                    'unit_amount' => $priceMinor,
                    'currency'    => strtolower($plan->currency ?? 'gbp'),
                    'recurring'   => ['interval' => $stripeInterval],
                    'product'     => $stripeProductId,
                ]);

                $plan->stripe_plan_id = $newStripePrice->id;
            }

            // ===== PAYPAL =====
            $verify = app()->environment('local') ? false : true;
            $paypal = new \GuzzleHttp\Client([
                'base_uri' => 'https://api-m.sandbox.paypal.com/',
                'verify'   => $verify ?? false,
            ]);
            $paypalToken = json_decode($paypal->post('v1/oauth2/token', [
                'auth'        => [config('services.paypal.client_id'), config('services.paypal.secret')],
                'form_params' => ['grant_type' => 'client_credentials'],
            ])->getBody(), true)['access_token'];

            $paypalProductId = config('services.paypal.product_id'); // Fixed product you provided
            $ppInterval = match ($validated['duration']) {
                'yearly' => 'YEAR',
                'weekly' => 'WEEK',
                default  => 'MONTH',
            };

            $needNewPaypalPlan = ($priceMinor !== $priceOld) || ($validated['duration'] !== $durationOld);

            if ($needNewPaypalPlan) {
                // Deactivate existing plan
                try {
                    $paypal->post("v1/billing/plans/{$plan->paypal_plan_id}/deactivate", [
                        'headers' => ['Authorization' => "Bearer $paypalToken"]
                    ]);
                } catch (\Throwable $e) {
                    // log but continue (plan might already be inactive)
                    \Log::warning('PayPal deactivate plan warning: '.$e->getMessage());
                }

                // Create a fresh plan with new price/duration
                $newPlan = json_decode($paypal->post('v1/billing/plans', [
                    'headers' => [
                        'Authorization' => "Bearer $paypalToken",
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'product_id'   => $paypalProductId,
                        'name'         => $validated['name'],
                        'description'  => 'Updated subscription plan for '.$validated['name'],
                        'billing_cycles' => [[
                            'frequency' => [
                                'interval_unit'  => $ppInterval,
                                'interval_count' => 1,
                            ],
                            'tenure_type'     => 'REGULAR',
                            'sequence'        => 1,
                            'total_cycles'    => 0,
                            'pricing_scheme'  => [
                                'fixed_price' => [
                                    'value'         => number_format($validated['price'], 2, '.', ''),
                                    'currency_code' => strtoupper($plan->currency ?? 'GBP'),
                                ],
                            ],
                        ]],
                        'payment_preferences' => [
                            'auto_bill_outstanding'     => true,
                            'setup_fee_failure_action'  => 'CONTINUE',
                            'payment_failure_threshold' => 1,
                        ],
                    ],
                ])->getBody(), true);

                $plan->paypal_plan_id = $newPlan['id'];
            } else {
                // Only name changed → patch PayPal plan name
                try {
                    $paypal->patch("v1/billing/plans/{$plan->paypal_plan_id}", [
                        'headers' => [
                            'Authorization' => "Bearer $paypalToken",
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => [[
                            'op'    => 'replace',
                            'path'  => '/name',
                            'value' => $validated['name'],
                        ]],
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('PayPal patch name warning: '.$e->getMessage());
                }
            }

            // ===== SAVE DB =====
            $plan->name        = $validated['name'];
            $plan->price_minor = $priceMinor;
            $plan->duration    = $validated['duration'];
            $plan->features    = $features;
            $plan->save();

            return response()->json(['message' => 'Plan updated successfully.','data'=>$plan], 200);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Log::error('Stripe update error: '.$e->getMessage());
            return response()->json(['message' => 'Stripe error: '.$e->getMessage()], 400);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $error = $e->getResponse()?->getBody()?->getContents() ?? $e->getMessage();
            \Log::error('PayPal update error: '.$error);
            return response()->json(['message' => 'PayPal error: '.$error], 400);
        } catch (\Throwable $e) {
            \Log::error('Plan update failed: '.$e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred.','error'=>$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $plan = Plan::find($id);
        if (!$plan) {
            return response()->json(['message'=>'Plan not found.'], 404);
        }

        // try {
        //     // ===== STRIPE: deactivate price (and optionally archive product) =====
        //     \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        //     try {
        //         \Stripe\Price::update($plan->stripe_plan_id, ['active' => false]);
        //     } catch (\Throwable $e) {
        //         \Log::warning('Stripe deactivate price warning: '.$e->getMessage());
        //     }

        //     // Optional: also archive product if you want (and if no other prices)
        //     try {
        //         $price  = \Stripe\Price::retrieve($plan->stripe_plan_id);
        //         $prodId = is_string($price->product) ? $price->product : $price->product->id;
        //         \Stripe\Product::update($prodId, ['active' => false]); // archive
        //     } catch (\Throwable $e) {
        //         \Log::info('Stripe product archive skipped: '.$e->getMessage());
        //     }

        //     // ===== PAYPAL: deactivate plan =====
        //     $verify = app()->environment('local') ? false : true;
        //     $paypal = new \GuzzleHttp\Client([
        //         'base_uri' => 'https://api-m.sandbox.paypal.com/',
        //         'verify'   => $verify,
        //     ]);

        //     $paypalToken = json_decode($paypal->post('v1/oauth2/token', [
        //         'auth'        => [config('services.paypal.client_id'), config('services.paypal.secret')],
        //         'form_params' => ['grant_type' => 'client_credentials'],
        //     ])->getBody(), true)['access_token'];

        //     try {
        //         $paypal->post("v1/billing/plans/{$plan->paypal_plan_id}/deactivate", [
        //             'headers' => ['Authorization' => "Bearer $paypalToken"],
        //         ]);
        //     } catch (\Throwable $e) {
        //         \Log::warning('PayPal deactivate warning: '.$e->getMessage());
        //     }

        //     // ===== DB: delete plan row =====
        //     $plan->delete();

        //     return response()->json(['message'=>'Plan deleted successfully.'], 200);

        // } catch (\Throwable $e) {
        //     \Log::error('Plan delete failed: '.$e->getMessage());
        //     return response()->json(['message'=>'Delete failed.','error'=>$e->getMessage()], 500);
        // }

        try {
            // Cancel all subscriptions for this plan
            $subscriptions = Subscription::where('plan_id', $id)->get();

            foreach ($subscriptions as $sub) {
                // Cancel in Stripe
                if ($sub->payment_provider === 'stripe' && $sub->payment_reference) {
                    try {
                        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

                        $session = \Stripe\Checkout\Session::retrieve($sub->payment_reference);

                        if (!empty($session->subscription)) {
                            \Stripe\Subscription::update($session->subscription, [
                                'cancel_at_period_end' => true,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("Stripe cancel failed for subscription {$sub->id}: " . $e->getMessage());
                    }
                }

                // Cancel in PayPal
                if ($sub->payment_provider === 'paypal' && $sub->payment_reference) {
                    try {
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
                            'json' => ['reason' => 'Package deleted by Super Admin'],
                        ]);
                    } catch (\Throwable $e) {
                        \Log::warning("PayPal cancel failed for subscription {$sub->id}: " . $e->getMessage());
                    }
                }

                // Mark the subscription as ended
                $sub->update(['ends_at' => now()]);
            }

            // Remove foreign key link before deleting plan
            // (Set plan_id = NULL for all subscriptions using this plan)
            \DB::table('subscriptions')->where('plan_id', $id)->update(['plan_id' => null]);

            // Delete the plan itself
            $plan->delete();

            // If deleted successfully → deactivate on Stripe and PayPal
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                \Stripe\Price::update($plan->stripe_plan_id, ['active' => false]);
                $price = \Stripe\Price::retrieve($plan->stripe_plan_id);
                $productId = is_string($price->product) ? $price->product : $price->product->id;
                \Stripe\Product::update($productId, ['active' => false]);
            } catch (\Throwable $e) {
                \Log::warning('Stripe deactivate warning: ' . $e->getMessage());
            }

            try {
                $paypal = new \GuzzleHttp\Client(['base_uri' => 'https://api-m.sandbox.paypal.com/']);
                $paypalToken = json_decode($paypal->post('v1/oauth2/token', [
                    'auth'        => [config('services.paypal.client_id'), config('services.paypal.secret')],
                    'form_params' => ['grant_type' => 'client_credentials'],
                ])->getBody(), true)['access_token'];

                $paypal->post("v1/billing/plans/{$plan->paypal_plan_id}/deactivate", [
                    'headers' => ['Authorization' => "Bearer $paypalToken"],
                ]);
            } catch (\Throwable $e) {
                \Log::warning('PayPal deactivate warning: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Plan deleted successfully and user subscriptions canceled.'
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('Plan delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Delete failed.',
                'error' => $e->getMessage(),
            ], 500);
    }
    }


}