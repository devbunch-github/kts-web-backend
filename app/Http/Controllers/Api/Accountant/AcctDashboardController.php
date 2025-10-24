<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Accountant;

class AcctDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Identify current accountant
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        if (!$accountant) {
            return response()->json(['message' => 'Accountant not found'], 404);
        }

        $accountId = $accountant->AccountId;

        // Fetch Income & Expenses by AccountId
        $incomes = Income::where('AccountId', $accountId)
            ->orderByDesc('PaymentDateTime')
            ->take(5)
            ->get(['Id', 'PaymentDateTime', 'Amount', 'CategoryId', 'Serviced', 'Description']);

        $expenses = Expense::where('AccountId', $accountId)
            ->orderByDesc('PaidDateTime')
            ->take(5)
            ->get(['Id', 'PaidDateTime', 'Amount', 'Supplier', 'CategoryId']);

        // Compute totals
        $incomeTotal = Income::where('AccountId', $accountId)->sum('Amount');
        $expenseTotal = Expense::where('AccountId', $accountId)->sum('Amount');
        $profit = $incomeTotal - $expenseTotal;

        // (Optional) Tax placeholder logic — you can later calculate properly
        $tax = $incomeTotal * 0.2;

        return response()->json([
            'income_total' => round($incomeTotal, 2),
            'expense_total' => round($expenseTotal, 2),
            'profit' => round($profit, 2),
            'tax' => round($tax, 2),
            'income_list' => $incomes,
            'expense_list' => $expenses,
        ]);
    }
}