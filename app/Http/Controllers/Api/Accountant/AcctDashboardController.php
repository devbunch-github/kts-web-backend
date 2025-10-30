<?php

namespace App\Http\Controllers\Api\Accountant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Accountant;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Service;
use App\Models\Customer;
use App\Models\IncomeEditLog;
use App\Models\ExpenseEditLog;

class AcctDashboardController extends Controller
{
    /**
     * Accountant Dashboard Summary
     */
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
            ->get(['Id', 'PaymentDateTime', 'Amount', 'CategoryId', 'ServiceId', 'Description']);

        $expenses = Expense::where('AccountId', $accountId)
            ->orderByDesc('PaidDateTime')
            ->take(5)
            ->get(['Id', 'PaidDateTime', 'Amount', 'Supplier', 'CategoryId']);

        // Compute totals
        $incomeTotal = Income::where('AccountId', $accountId)->sum('Amount');
        $expenseTotal = Expense::where('AccountId', $accountId)->sum('Amount');
        $profit = $incomeTotal - $expenseTotal;

        // (Optional) Tax placeholder logic
        $tax = $incomeTotal * 0.2;

        return response()->json([
            'income_total' => round($incomeTotal, 2),
            'expense_total' => round($expenseTotal, 2),
            'profit' => round($profit, 2),
            'tax' => round($tax, 2),
            'income_list' => $incomes,
            'expense_list' => $expenses,
            'acct_data' => $accountant,
        ]);
    }

    /**
     * Fetch all incomes for the accountant dashboard Income/Sales page.
     */
    public function fetchAccountantIncome(Request $request)
    {
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        if (!$accountant) {
            return response()->json(['message' => 'Accountant not found'], 404);
        }

        $accountId = $accountant->AccountId;

        /**
         * ✅ Explanation:
         * - We directly LEFT JOIN to appointments, services, and customers.
         * - If ServiceId / CustomerId are NULL in Income, we use Appointment.* values.
         * - We alias everything so naming is consistent.
         */
        $records = DB::table('Income as i')
            ->leftJoin('Appointments as a', 'i.AppointmentId', '=', 'a.Id')
            ->leftJoin('Services as s', DB::raw('ISNULL(i.ServiceId, a.ServiceId)'), '=', 's.Id')
            ->leftJoin('Customers as c', DB::raw('ISNULL(i.CustomerId, a.CustomerId)'), '=', 'c.Id')
            ->where('i.AccountId', $accountId)
            ->orderByDesc('i.PaymentDateTime')
            ->selectRaw('
                i.Id,
                i.PaymentDateTime,
                i.Amount,
                i.PaymentMethod,
                i.Description,
                ISNULL(i.ServiceId, a.ServiceId) AS ServiceId,
                s.Name AS ServiceName,
                ISNULL(i.CustomerId, a.CustomerId) AS CustomerId,
                c.Name AS CustomerName,
                s.CategoryId AS CategoryId
            ')
            ->get();

        // ✅ Add category name placeholder (since Category table has no name column)
        $data = $records->map(function ($row) {
            return [
                'Id' => $row->Id,
                'PaymentDateTime' => $row->PaymentDateTime,
                'Amount' => $row->Amount,
                'PaymentMethod' => ($row->PaymentMethod == '0')? 'Cash' : 'Bank',
                'Description' => $row->Description,
                'ServiceId' => $row->ServiceId,
                'ServiceName' => $row->ServiceName,
                'CustomerId' => $row->CustomerId,
                'CustomerName' => $row->CustomerName,
                'CategoryId' => $row->CategoryId,
                'CategoryName' => null,
            ];
        });

        return response()->json($data);
    }

    /**
     * Delete an income record belonging to the accountant's account.
     */
    public function deleteIncome(Request $request, $id)
    {
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        if (!$accountant) {
            return response()->json(['message' => 'Accountant not found'], 404);
        }

        $accountId = $accountant->AccountId;

        $income = Income::where('AccountId', $accountId)->find($id);
        if (!$income) {
            return response()->json(['message' => 'Income not found'], 404);
        }

        $income->delete();

        return response()->json(['message' => 'Income deleted successfully']);
    }

    /**
     * Fetch all Expenses for the accountant dashboard Expense page.
     */
    public function fetchAccountantExpenses(Request $request)
    {
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();
        if (!$accountant) {
            return response()->json(['message' => 'Accountant not found'], 404);
        }

        $accountId = $accountant->AccountId;

        $expenses = Expense::where('AccountId', $accountId)
            ->orderByDesc('PaidDateTime')
            ->get([
                'Id',
                'PaidDateTime',
                'Supplier',
                'Amount',
                'CategoryId',
                'PaymentMethod',
                'ReciptId',
                'Notes'
            ]);

        $data = $expenses->map(function ($row) {
            return [
                'Id' => $row->Id,
                'PaidDateTime' => $row->PaidDateTime,
                'Supplier' => $row->Supplier,
                'Amount' => $row->Amount,
                'PaymentMethod' => ($row->PaymentMethod == '0')? 'Cash' : 'Bank',
                'CategoryName' => optional($row->category)->name ?? null,
                'ReceiptUrl' => $row->receipt?->url ?? null,
                'Notes' => $row->Notes,
            ];
        });

        return response()->json($data);
    }

    /**
     * Delete an expense record belonging to the accountant's account.
     */
    public function deleteExpense(Request $request, $id)
    {
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        if (!$accountant) {
            return response()->json(['message' => 'Accountant not found'], 404);
        }

        $accountId = $accountant->AccountId;
        $expense = Expense::where('AccountId', $accountId)->find($id);

        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        $expense->delete();
        return response()->json(['message' => 'Expense deleted successfully']);
    }

    public function fetchExpenseCategories(Request $request)
    {
        try {
            $user = $request->user();
            $accountant = Accountant::where('email', $user->email)->first();

            if (!$accountant) {
                return response()->json(['message' => 'Accountant not found'], 404);
            }

            $accountId = $accountant->AccountId;

            $categories = Category::where('AccountId', $accountId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            // ✅ If no categories found, return some safe defaults
            if ($categories->isEmpty()) {
                $defaultCategories = collect([
                    ['id' => 0, 'name' => 'Accountancy'],
                    ['id' => 0, 'name' => 'Travel'],
                    ['id' => 0, 'name' => 'Utilities'],
                    ['id' => 0, 'name' => 'Supplies'],
                    ['id' => 0, 'name' => 'Miscellaneous'],
                ]);

                return response()->json($defaultCategories);
            }

            return response()->json($categories);

        } catch (\Throwable $e) {
            // ✅ Log and return fallback defaults in case of any exception
            Log::error('Error fetching expense categories: ' . $e->getMessage());

            $fallback = collect([
                ['id' => 0, 'name' => 'Accountancy'],
                ['id' => 0, 'name' => 'Travel'],
                ['id' => 0, 'name' => 'Utilities'],
                ['id' => 0, 'name' => 'Supplies'],
                ['id' => 0, 'name' => 'Miscellaneous'],
            ]);

            return response()->json($fallback, 200);
        }
    }

    public function fetchIncomeById($id, Request $request)
    {
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        $income = Income::where('AccountId', $accountant->AccountId)
            ->where('Id', $id)
            ->first();

        if (!$income) {
            return response()->json(['message' => 'Income record not found.'], 404);
        }

        return response()->json($income);
    }


    public function updateIncome(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Description' => 'required|string|max:255',
            'Amount' => 'required|numeric',
            'PaymentMethod' => 'nullable|string|max:100',
            'PaymentDateTime' => 'nullable|date',
            'reason' => 'required|string|max:500',
        ], [
            'Amount.numeric' => 'The amount must be a valid number (negative values allowed).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();
        $income = Income::where('Id', $id)
            ->where('AccountId', $accountant->AccountId)
            ->first();

        if (!$income) {
            return response()->json(['message' => 'Income not found.'], 404);
        }

        // Track changes
        $changes = [];
        foreach (['Description', 'Amount', 'PaymentMethod', 'PaymentDateTime'] as $field) {
            if ($income->$field != $request->$field) {
                $changes[] = [
                    'field_name' => $field,
                    'old_value' => $income->$field,
                    'new_value' => $request->$field,
                    'reason' => $request->reason,
                    'income_id' => $income->Id,
                    'edited_by' => $accountant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($changes)) {
            IncomeEditLog::insert($changes);
        }

        // Update main record
        $income->update([
            'Description' => $request->Description,
            'Amount' => $request->Amount,
            'PaymentMethod' => $request->PaymentMethod,
            'PaymentDateTime' => $request->PaymentDateTime,
            'ModifiedById' => $accountant->id,
            'DateModified' => now(),
        ]);

        return response()->json([
            'message' => 'Income updated successfully.',
            'income' => $income,
        ]);
    }

    public function fetchExpenseById($id, Request $request)
    {
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        $expense = Expense::where('AccountId', $accountant->AccountId)
            ->where('Id', $id)
            ->first();

        if (!$expense) {
            return response()->json(['message' => 'Expense not found.'], 404);
        }

        return response()->json($expense);
    }


    public function updateExpense(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Supplier' => 'required|string|max:255',
            'Amount' => 'required|numeric',
            'PaidDateTime' => 'nullable|date',
            'Notes' => 'nullable|string|max:1000',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        $expense = Expense::where('AccountId', $accountant->AccountId)
            ->where('Id', $id)
            ->first();

        if (!$expense) {
            return response()->json(['message' => 'Expense not found.'], 404);
        }

        // Log changes
        $changes = [];
        foreach (['Supplier', 'Amount', 'PaidDateTime', 'Notes'] as $field) {
            if ($expense->$field != $request->$field) {
                $changes[] = [
                    'field_name' => $field,
                    'old_value' => $expense->$field,
                    'new_value' => $request->$field,
                    'reason' => $request->reason,
                    'expense_id' => $expense->Id,
                    'edited_by' => $accountant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($changes)) {
            ExpenseEditLog::insert($changes);
        }

        // Update the expense record
        $expense->update([
            'Supplier' => $request->Supplier,
            'Amount' => $request->Amount,
            'PaidDateTime' => $request->PaidDateTime,
            'Notes' => $request->Notes,
            'ModifiedById' => $accountant->id,
            'DateModified' => now(),
        ]);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'expense' => $expense,
        ]);
    }
}