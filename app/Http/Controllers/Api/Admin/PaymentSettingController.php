<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentSetting;
use App\Models\User;

class PaymentSettingController extends Controller
{
    public function show()
    {
        $setting = PaymentSetting::first();
        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_client_secret' => 'nullable|string|max:255',
            'paypal_email' => 'nullable|string|max:255',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($request->user_id);

        $setting = PaymentSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'paypal_client_id' => $validated['paypal_client_id'] ?? null,
                'paypal_client_secret' => $validated['paypal_client_secret'] ?? null,
                'paypal_email' => $validated['paypal_email'] ?? null,
                'stripe_public_key' => $validated['stripe_public_key'] ?? null,
                'stripe_secret_key' => $validated['stripe_secret_key'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment settings saved successfully.',
            'data' => $setting
        ]);
    }
}