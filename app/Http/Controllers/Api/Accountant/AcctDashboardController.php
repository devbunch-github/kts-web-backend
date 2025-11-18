<?php

namespace App\Http\Controllers\Api\Accountant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;
use ZipArchive;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Expenses;
use App\Models\ExpenseCategory;
use App\Models\Accountant;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Service;
use App\Models\Customer;
use App\Models\IncomeEditLog;
use App\Models\ExpenseEditLog;
use App\Models\AccountingPeriod;
use App\Models\File as FileModel;
use App\Exports\SummaryExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Business\BusinessDashboardService;

class AcctDashboardController extends Controller
{

    public function __construct(private BusinessDashboardService $service) {}

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

    public function dbsummary(Request $request)
    {
        $user = $request->user();
        $accountant = Accountant::where('email', $user->email)->first();

        if (!$accountant) {
            return response()->json(['message' => 'Accountant not found'], 404);
        }

        $accountId = $accountant->AccountId;
        return response()->json([
            'success' => true,
            'data' => $this->service->getAccountingOverview($request, $accountId),
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

        $today = now();

        // ✅ Determine the active fiscal year for this accountant
        $activeYear = DB::table('AccountingPeriods')
            ->where('AccountId', $accountId)
            ->whereDate('StartDateTime', '<=', $today)
            ->whereDate('EndDateTime', '>=', $today)
            ->first();

        if (!$activeYear) {
            // fallback — latest one for this account
            $activeYear = DB::table('AccountingPeriods')
                ->where('AccountId', $accountId)
                ->orderByDesc('StartDateTime')
                ->first();
        }

        // default filter to current active period
        $startDate = $request->input('start_date', $activeYear->StartDateTime ?? "$today->year-04-06");
        $endDate = $request->input('end_date', $activeYear->EndDateTime ?? ($today->year + 1) . "-04-05");

        $records = DB::table('Income as i')
            ->leftJoin('Appointments as a', 'i.AppointmentId', '=', 'a.Id')
            ->leftJoin('Services as s', DB::raw('ISNULL(i.ServiceId, a.ServiceId)'), '=', 's.Id')
            ->leftJoin('Customers as c', DB::raw('ISNULL(i.CustomerId, a.CustomerId)'), '=', 'c.Id')
            ->where('i.AccountId', $accountId)
            ->whereBetween('i.PaymentDateTime', [$startDate, $endDate])
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

        $data = $records->map(function ($row) {
            return [
                'Id' => $row->Id,
                'PaymentDateTime' => $row->PaymentDateTime,
                'Amount' => $row->Amount,
                'PaymentMethod' => ($row->PaymentMethod == '0') ? 'Cash' : 'Bank',
                'Description' => $row->Description,
                'ServiceId' => $row->ServiceId,
                'ServiceName' => $row->ServiceName,
                'CustomerId' => $row->CustomerId,
                'CustomerName' => $row->CustomerName,
                'CategoryId' => $row->CategoryId,
                'CategoryName' => null,
            ];
        });

        // ✅ Hardcode the available fiscal years (23-24, 24-25, 25-26)
        $years = [
            [
                'label' => '2023-2024',
                'start' => '2023-04-06',
                'end' => '2024-04-05',
            ],
            [
                'label' => '2024-2025',
                'start' => '2024-04-06',
                'end' => '2025-04-05',
            ],
            [
                'label' => '2025-2026',
                'start' => '2025-04-06',
                'end' => '2026-04-05',
            ],
        ];

        return response()->json([
            'incomes' => $data,
            'active_fiscal_year' => [
                'label' => $activeYear ? date('Y', strtotime($activeYear->StartDateTime)) . '-' . date('Y', strtotime($activeYear->EndDateTime)) : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'available_years' => $years,
        ]);
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
        $today = now();

        // ✅ Determine the current active fiscal year for this accountant
        $activeYear = DB::table('AccountingPeriods')
            ->where('AccountId', $accountId)
            ->whereDate('StartDateTime', '<=', $today)
            ->whereDate('EndDateTime', '>=', $today)
            ->first();

        // Fallback if no active fiscal year found
        if (!$activeYear) {
            $activeYear = DB::table('AccountingPeriods')
                ->where('AccountId', $accountId)
                ->orderByDesc('StartDateTime')
                ->first();
        }

        // ✅ Fiscal year filter (default to active)
        $startDate = $request->input('start_date', $activeYear->StartDateTime ?? "$today->year-04-06");
        $endDate = $request->input('end_date', $activeYear->EndDateTime ?? ($today->year + 1) . "-04-05");

        // ✅ Fetch filtered expenses
        $expenses = Expense::with(['category'])
            ->where('AccountId', $accountId)
            ->whereBetween('PaidDateTime', [$startDate, $endDate])
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

        // ✅ Map to structured response
        $data = $expenses->map(function ($row) {
            return [
                'Id' => $row->Id,
                'PaidDateTime' => $row->PaidDateTime,
                'Supplier' => $row->Supplier,
                'Amount' => $row->Amount,
                'PaymentMethod' => ($row->PaymentMethod == '0') ? 'Cash' : 'Bank',
                // 'CategoryId' => $row->CategoryId,
                'CategoryName' => optional($row->category)->name ?? null,
                'ReceiptUrl' => $row->receipt?->url ?? null,
                'Notes' => $row->Notes,
            ];
        });

        // ✅ Predefined fiscal year options (3 years)
        $years = [
            [
                'label' => '2023-2024',
                'start' => '2023-04-06',
                'end' => '2024-04-05',
            ],
            [
                'label' => '2024-2025',
                'start' => '2024-04-06',
                'end' => '2025-04-05',
            ],
            [
                'label' => '2025-2026',
                'start' => '2025-04-06',
                'end' => '2026-04-05',
            ],
        ];

        // ✅ Return unified response
        return response()->json([
            'expenses' => $data,
            'active_fiscal_year' => [
                'label' => $activeYear
                    ? date('Y', strtotime($activeYear->StartDateTime)) . '-' . date('Y', strtotime($activeYear->EndDateTime))
                    : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'available_years' => $years,
        ]);
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

    /**
     * 🔹 Generate PDF Summary Report
     */
    public function summary(Request $request)
    {
        $currentDate = Carbon::now();

        // Determine fiscal year start/end
        if ($currentDate < Carbon::create($currentDate->year, 4, 6, 0, 0, 0)) {
            $fiscalYearStart = Carbon::create($currentDate->year - 1, 4, 6, 0, 0, 0);
        } else {
            $fiscalYearStart = Carbon::create($currentDate->year, 4, 6, 0, 0, 0);
        }

        $fiscalYearEnd = $fiscalYearStart->copy()->addYear()->subDay();

        $start_date = $request->query('start_date')
            ? date('Y-m-d H:i:s', strtotime($request->query('start_date')))
            : date('Y-m-d H:i:s', strtotime($fiscalYearStart));

        $start_date = Carbon::parse($start_date)->subHours(5);
        $end_date = $request->query('end_date')
            ? date('Y-m-d H:i:s', strtotime($request->query('end_date')))
            : date('Y-m-d H:i:s', strtotime($fiscalYearEnd));

        $response = [];
        $user = Auth::user();

        $accountant = Accountant::where('email', $user->email)->first();
        if (empty($accountant)) {
            return response()->json(['msg' => 'Accountant not found'], 422);
        }

        $account = $accountant;
        $account_period_id = AccountingPeriod::where('AccountId', $account->AccountId)->first();

        if (empty($account_period_id)) {
            return response()->json(['msg' => 'Accounting Period not found'], 422);
        }

        // --- Incomes ---
        $income = Income::select(
            'Id as id',
            'Amount as amount',
            'Description as description',
            DB::raw("(CASE WHEN PaymentMethod = 1 THEN 'Bank' ELSE 'Cash' END) AS paymentMethod"),
            'PaymentDateTime as paymentDateTime'
        )
            ->orderBy('paymentDateTime', 'desc')
            ->whereBetween('PaymentDateTime', [$start_date, $end_date])
            ->where('AccountId', $account->AccountId)
            ->get();

        $revenue = $income->sum('amount');

        // --- Expenses ---
        $expense = Expenses::select(
            'Id as id',
            'Amount as amount',
            'Notes as notes',
            DB::raw("(CASE WHEN PaymentMethod = 1 THEN 'Bank' ELSE 'Cash' END) AS paymentMethod"),
            'PaidDateTime as paidDateTime',
            'Supplier as supplier'
        )
            ->where('AccountId', $account->AccountId)
            ->whereBetween('paidDateTime', [$start_date, $end_date])
            ->get();

        $expenses = $expense->sum('amount');
        $profit = $revenue - $expenses;

        // Initialize arrays to prevent undefined errors
        $this_year_expense = $previous_year_expense = [];
        $this_year_total_expense = $previous_year_total_expense = collect();

        // --- This year expense breakdown ---
        $this_year_total_expense = Expenses::groupBy('CategoryId')
            ->selectRaw('sum(Amount) as sum, CategoryId')
            ->where('AccountId', $account->AccountId)
            ->whereBetween('PaidDateTime', [$start_date, $end_date])
            ->pluck('sum', 'CategoryId');

        $not_selected = $this_year_total_expense->keys();
        $not_selected_category = ExpenseCategory::whereNotIn('Id', $not_selected)->pluck('Id');

        foreach ($this_year_total_expense as $key => $value) {
            $expense_category['total'] = (float) $value;
            $expense_category['category'] = ExpenseCategory::select('Id as id', 'Name as name')
                ->where('Id', $key)
                ->first();
            $this_year_expense[] = $expense_category;
        }

        foreach ($not_selected_category as $category) {
            $not_selected_category_arr['total'] = 0.0;
            $not_selected_category_arr['category'] = ExpenseCategory::select('Id as id', 'Name as name')
                ->where('Id', $category)
                ->first();
            $this_year_expense[] = $not_selected_category_arr;
        }

        // --- Previous year expense breakdown ---
        $account_period = AccountingPeriod::where('AccountId', $account->AccountId)
            ->select('Id as id', 'StartDateTime as periodStartDate', 'EndDateTime as periodEndDate')
            ->first();

        $previous_start_date = Carbon::parse($account_period->periodStartDate)->copy()->subYear()->subHours(5);
        $previous_end_date = Carbon::parse($account_period->periodEndDate)->copy()->subYear();

        $previous_year_total_expense = Expenses::groupBy('CategoryId')
            ->selectRaw('sum(Amount) as sum, CategoryId')
            ->where('AccountId', $account->AccountId)
            ->whereBetween('PaidDateTime', [$previous_start_date, $previous_end_date])
            ->pluck('sum', 'CategoryId');

        $previous_year_not_selected = $previous_year_total_expense->keys();
        $previous_year_not_selected_category = ExpenseCategory::whereNotIn('Id', $previous_year_not_selected)->pluck('Id');

        foreach ($previous_year_total_expense as $key => $value) {
            $previous_year_expense_category['total'] = (float) $value;
            $previous_year_expense_category['category'] = ExpenseCategory::select('Id as id', 'Name as name')
                ->where('Id', $key)
                ->first();
            $previous_year_expense[] = $previous_year_expense_category;
        }

        foreach ($previous_year_not_selected_category as $category) {
            $previous_year_not_selected_category_arr['total'] = 0.0;
            $previous_year_not_selected_category_arr['category'] = ExpenseCategory::select('Id as id', 'Name as name')
                ->where('Id', $category)
                ->first();
            $previous_year_expense[] = $previous_year_not_selected_category_arr;
        }

        // --- Income and tips ---
        $previous_year_income = Income::where('AccountId', $account->AccountId)
            ->whereBetween('PaymentDateTime', [$previous_start_date, $previous_end_date])
            ->get();

        $this_year_income = Income::where('AccountId', $account->AccountId)
            ->whereBetween('PaymentDateTime', [$start_date, $end_date])
            ->get();

        $previous_year_tips = Appointment::where('AccountId', $account->AccountId)
            ->where('Tip', '>', 0)
            ->whereBetween('StartDateTime', [$previous_start_date, $previous_end_date])
            ->get(['Tip', 'StartDateTime as paymentDateTime'])
            ->toArray();

        $this_year_tips = Appointment::where('AccountId', $account->AccountId)
            ->where('Tip', '>', 0)
            ->whereBetween('StartDateTime', [$start_date, $end_date])
            ->get(['Tip', 'StartDateTime as paymentDateTime'])
            ->toArray();

        // --- Prepare data for PDF ---
        $data = [
            'title' => 'KTS Expense Category Details',
            'this_year_total_expense' => $this_year_total_expense,
            'this_year_expense' => $this_year_expense,
            'previous_year_total_expense' => $previous_year_total_expense,
            'previous_year_expense' => $previous_year_expense,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'previous_start_date' => $previous_start_date,
            'previous_end_date' => $previous_end_date,
            'this_year_income' => $this_year_income,
            'this_year_tips' => $this_year_tips,
            'previous_year_income' => $previous_year_income,
            'previous_year_tips' => $previous_year_tips,
            'user' => $user,
            'taxLiability' => $response['taxLiability'] ?? 0,
        ];

        // --- Generate PDF ---
        try {
            // Generate PDF content
            $pdf = Pdf::setOptions([
                'dpi' => 150,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'sans-serif'
            ])->loadView('pdf.summary_details', $data);

            $filename = 'summary_details_' . date('His') . '.pdf';
            $pdfDir = public_path('pdf');

            if (!file_exists($pdfDir)) {
                mkdir($pdfDir, 0755, true);
            }

            $filePath = $pdfDir . '/' . $filename;

            // Save to disk safely
            $pdfOutput = $pdf->output();
            if (empty($pdfOutput)) {
                throw new \Exception("PDF generation failed — no content returned from DomPDF.");
            }

            file_put_contents($filePath, $pdfOutput);

            $response['export_url'] = asset('pdf/' . $filename);
            // return response()->json($response);
            return response()->download(public_path('pdf/' . $filename));

        } catch (\Throwable $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔹 Generate CSV (ZIP) Summary Report
     */
    public function summaryCSV(Request $request)
    {
        $currentDate = Carbon::now();

        // Determine fiscal year start/end
        if ($currentDate < Carbon::create($currentDate->year, 4, 6, 0, 0, 0)) {
            $fiscalYearStart = Carbon::create($currentDate->year - 1, 4, 6, 0, 0, 0);
        } else {
            $fiscalYearStart = Carbon::create($currentDate->year, 4, 6, 0, 0, 0);
        }

        $fiscalYearEnd = $fiscalYearStart->copy()->addYear()->subDay();

        $start_date = Carbon::parse($request->start_date)->subHour()->format('Y-m-d H:i:s');
        $end_date = $request->end_date;

        $response = [];
        $user = Auth::user();

        $accountant = Accountant::where('email', $user->email)->first();
        if (empty($accountant)) {
            return response()->json(['msg' => 'Accountant not found'], 422);
        }

        $account = $accountant;
        $not_selected = [20, 21, 22];

        // ✅ Income
        $incomes = Income::select(
            'Amount as amount',
            'Description as description',
            DB::raw("(CASE WHEN PaymentMethod = 1 THEN 'Bank' ELSE 'Cash' END) AS paymentMethod"),
            'PaymentDateTime as paymentDateTime'
        )
            ->where('AccountId', $account->AccountId)
            ->whereBetween('PaymentDateTime', [$start_date, $end_date])
            ->orderBy('PaymentDateTime', 'DESC')
            ->get();

        // ✅ Tips
        $tips = Appointment::select('Tip as amount', 'StartDateTime as paymentDateTime')
            ->where('Tip', '>', 0)
            ->where('AccountId', $account->AccountId)
            ->whereBetween('StartDateTime', [$start_date, $end_date])
            ->whereNull('CancellationDate')
            ->orderBy('DateCreated', 'DESC')
            ->get();

        // ✅ Pensions, Drawings, Loan
        $pensions = Expenses::where('AccountId', $account->AccountId)
            ->where('CategoryId', 20)
            ->whereBetween('PaidDateTime', [$start_date, $end_date])
            ->get();

        $drawings = Expenses::where('AccountId', $account->AccountId)
            ->where('CategoryId', 21)
            ->whereBetween('PaidDateTime', [$start_date, $end_date])
            ->get();

        $loan = Expenses::where('AccountId', $account->AccountId)
            ->where('CategoryId', 22)
            ->whereBetween('PaidDateTime', [$start_date, $end_date])
            ->get();

        // ✅ Expense by categories
        $expense_categories = ExpenseCategory::whereNotIn('Id', $not_selected)
            ->orderBy('Name', 'ASC')
            ->pluck('Id', 'Name');

        $expense = [];
        foreach ($expense_categories as $key => $expense_category) {
            $expense[$key] = Expenses::select(
                'Amount as amount',
                'Notes as notes',
                DB::raw("(CASE WHEN PaymentMethod = 1 THEN 'Bank' ELSE 'Cash' END) AS paymentMethod"),
                'PaidDateTime as paidDateTime',
                'Supplier as supplier',
                'CategoryId'
            )
                ->where('AccountId', $account->AccountId)
                ->whereBetween('PaidDateTime', [$start_date, $end_date])
                ->where('CategoryId', $expense_category)
                ->orderBy('PaidDateTime', 'DESC')
                ->get();
        }

        // ✅ Build CSV
        $data = [
            'account' => $account,
            'incomes' => $incomes,
            'tips' => $tips,
            'expenses' => $expense,
            'pensions' => $pensions,
            'drawings' => $drawings,
            'loan' => $loan,
        ];

        $export = new SummaryExport($data);
        $filename = 'summary_export_csv_' . date('dmHis') . '.xlsx';
        $export->store($filename, 'summary_csv');

        $csv_file_path = public_path('csv/' . $filename);
        $zipFilename = 'summary_export_' . date('dmHis') . '.zip';
        $zipFilePath = public_path('zip/' . $zipFilename);

        $zip = new ZipArchive();
        $files_to_be_unlinked = [];

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === true) {
            if (file_exists($csv_file_path)) {
                $zip->addFile($csv_file_path, $filename);
            }

            $all_expenses = Expenses::select('ReciptId')
                ->whereNotNull('ReciptId')
                ->where('AccountId', $account->AccountId)
                ->whereBetween('PaidDateTime', [$start_date, $end_date])
                ->get();

            foreach ($all_expenses as $exp) {
                if (!empty($exp->ReciptId)) {
                    $files = FileModel::select('Identifier', 'Name as name')->where('receipt_id', @$exp->ReciptId)->get();
                    foreach ($files as $file) {
                        try {
                            $final_file_link = Storage::disk('azure')->url($account->AccountId . '/files/' . $file->name);
                            $imageContent = @file_get_contents($final_file_link);
                            if ($imageContent !== false) {
                                $imageName = basename($final_file_link);
                                $tempImageFilePath = public_path('images/' . $imageName);
                                file_put_contents($tempImageFilePath, $imageContent);
                                if (file_exists($tempImageFilePath)) {
                                    $zip->addFile($tempImageFilePath, 'Receipt_' . substr($imageName, -10));
                                    $files_to_be_unlinked[] = $tempImageFilePath;
                                }
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }

            $zip->close();
        }

        foreach ($files_to_be_unlinked as $tempImageFilePath) {
            @unlink($tempImageFilePath);
        }

        $response['export_csv_url'] = asset('zip/' . $zipFilename);
        // return response()->json($response);
        return response()->download(public_path('exports/summary_csv/' . $filename));
    }
}