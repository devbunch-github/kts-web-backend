<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Expense;
use Carbon\Carbon;

class ExpenseController extends Controller
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

        // Example: You can later replace this with your real Expense model
        $expenses = Expense::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get(['supplier', 'payment_provider', 'amount', 'created_at']);

        if ($expenses->isEmpty()) {
            // fallback dummy demo data
            $expenses = collect([
                [
                    'supplier' => 'Demo',
                    'payment_provider' => 'Paypal',
                    'amount' => 1000,
                    'created_at' => now()->subDays(15),
                ],
                [
                    'supplier' => 'Demo',
                    'payment_provider' => 'Stripe',
                    'amount' => 1500,
                    'created_at' => now()->subDays(10),
                ],
                [
                    'supplier' => 'Demo',
                    'payment_provider' => 'Pay at venue',
                    'amount' => 500,
                    'created_at' => now()->subDays(5),
                ],
            ]);
        }

        $data = $expenses->map(function ($item) {
            return [
                'supplier' => $item['supplier'],
                'payment_method' => ucfirst($item['payment_provider']),
                'payment_date' => Carbon::parse($item['created_at'])->format('d-m-Y'),
                'amount' => number_format($item['amount'], 2),
            ];
        });

        $total = $data->sum(fn ($i) => (float) str_replace(',', '', $i['amount']));

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'total_expense' => number_format($total, 2),
            'currency' => '£',
            'data' => $data,
        ]);
    }
}