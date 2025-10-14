<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BkUser;
use App\Models\Account;
use App\Models\Expense;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function show($user_id)
    {
        // Step 1: Get Laravel User
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Step 2: Match BkUser (SQL Server)
        $bkUser = BkUser::where('Email', $user->email)
            ->orWhere('DisplayName', 'LIKE', "%{$user->name}%")
            ->first();

        if (!$bkUser) {
            return response()->json([
                'success' => false,
                'message' => 'BkUser not found for this user.'
            ], 404);
        }

        // Step 3: Get Account
        $account = $bkUser->account;
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for this BkUser.'
            ], 404);
        }

        // Step 4: Fetch Expense records from SQL Server
        $expenses = Expense::where('AccountId', $account->Id)
            ->orderBy('PaidDateTime', 'desc')
            ->get();

        if ($expenses->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No expense records found.',
                'total_expense' => '0.00',
                'currency' => '£',
                'data' => [],
            ]);
        }

        // Step 5: Map PaymentMethod integers to readable names
        $methodMap = [
            1 => 'Stripe',
            2 => 'Card',
            3 => 'Cash',
            4 => 'Paypal',
            5 => 'Bank Transfer',
            6 => 'Pay at Venue',
        ];

        // Step 6: Format data
        $formatted = $expenses->map(function ($expense) use ($methodMap) {
            return [
                'supplier' => $expense->Supplier ?? 'N/A',
                'payment_method' => $methodMap[$expense->PaymentMethod] ?? 'Other',
                'payment_date' => $expense->PaidDateTime
                    ? Carbon::parse($expense->PaidDateTime)->format('d-m-Y')
                    : ($expense->DateCreated ? Carbon::parse($expense->DateCreated)->format('d-m-Y') : ''),
                'amount' => number_format((float) $expense->Amount, 2),
                'notes' => $expense->Notes ?? '',
            ];
        });

        // Step 7: Calculate total expense
        $total = $expenses->sum(fn($e) => (float) $e->Amount);

        // Step 8: Return structured response
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'bk_user' => [
                'id' => $bkUser->Id,
                'display_name' => $bkUser->DisplayName,
                'email' => $bkUser->Email,
            ],
            'account' => [
                'id' => $account->Id,
            ],
            'total_expense' => number_format($total, 2),
            'currency' => '£',
            'data' => $formatted,
        ]);
    }
}
