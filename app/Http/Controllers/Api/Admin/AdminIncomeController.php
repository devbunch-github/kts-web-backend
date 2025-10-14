<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BkUser;
use App\Models\Account;
use App\Models\Income;
use App\Models\Subscription;
use Carbon\Carbon;

class AdminIncomeController extends Controller
{
    public function show($user_id)
    {
        // Step 1: Get the base user (from main Laravel app DB)
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Step 2: Try to find matching user in BkUsers (via SQL Server)
        $bkUser = BkUser::where('Email', $user->email)
            ->orWhere('DisplayName', 'LIKE', "%{$user->name}%")
            ->first();

        if (!$bkUser) {
            return response()->json([
                'success' => false,
                'message' => 'BkUser not found for this user.'
            ], 404);
        }

        // Step 3: Get the related Account record (via UserId in BkUsers)
        $account = $bkUser->account; // Relationship defined in model

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for this user in Accounts table.'
            ], 404);
        }

        // Step 4: Get income records from Income table
        $incomes = Income::where('AccountId', $account->Id)
            ->orderBy('DateCreated', 'desc')
            ->get();

        if ($incomes->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No income records found.',
                'data' => [],
                'total_income' => '0.00',
                'currency' => '£',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        $subscription = Subscription::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->first();

        $subscriptionMethod = $subscription ? ucfirst($subscription->payment_provider ?? 'Unknown') : 'Unknown';

        // Step 5: Map PaymentMethod int → readable text
        $methodMap = [
            1 => 'Cash',
            2 => 'Card',
            3 => 'Stripe',
            4 => 'Paypal',
            5 => 'Bank Transfer',
            6 => 'Pay at Venue',
        ];

        // Step 6: Format incomes and compute total
        $formatted = $incomes->map(function ($income) use ($methodMap, $subscriptionMethod) {
            $amount = (float) $income->Amount;

            // Apply refund adjustments
            if ($income->IsRefund) {
                $amount -= (float) ($income->RefundAmount ?? 0);
            }

            return [
                'description' => $income->Description ?? 'Income',
                'payment_method' => $subscriptionMethod ?? ($methodMap[$income->PaymentMethod] ?? 'Other'),
                'payment_date' => $income->PaymentDateTime
                    ? Carbon::parse($income->PaymentDateTime)->format('d-m-Y')
                    : Carbon::parse($income->DateCreated)->format('d-m-Y'),
                'amount' => number_format($amount, 2),
                'is_refund' => (bool) $income->IsRefund,
                'notes' => $income->Notes ?? '',
            ];
        });

        // Step 7: Calculate total income (subtract refunds)
        $total = $incomes->sum(function ($income) {
            $amount = (float) $income->Amount;
            if ($income->IsRefund) {
                $amount -= (float) ($income->RefundAmount ?? 0);
            }
            return $amount;
        });

        // Step 8: Response
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
            'total_income' => number_format($total, 2),
            'currency' => '£',
            'data' => $formatted,
        ]);
    }
}
