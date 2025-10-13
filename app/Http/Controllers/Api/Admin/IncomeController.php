<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Payment;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;

class IncomeController extends Controller
{
    public function show($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Fetch subscriptions or payments associated with this user
        $subscriptions = Subscription::with('plan')
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // For demo purpose – simulate some income data
        // You can replace this with your payments table later
        $incomes = $subscriptions->map(function ($sub) {
            return [
                'description' => 'Monthly Sales',
                'payment_method' => ucfirst($sub->payment_provider ?? 'unknown'),
                'payment_date' => Carbon::parse($sub->created_at)->format('d-m-Y'),
                'amount' => number_format(($sub->plan?->price_minor ?? 0) / 100, 2),
                'currency' => $sub->plan?->currency ?? 'GBP',
            ];
        });

        $total = $incomes->sum(function ($i) {
            return (float) $i['amount'];
        });

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'total_income' => number_format($total, 2),
            'currency' => '£',
            'data' => $incomes,
        ]);
    }
}