<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\ExpenseRepositoryInterface;
use App\Models\Expenses;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class ExpenseRepository implements ExpenseRepositoryInterface
{
    public function list($request)
    {
        $accountId = auth()->user()->bkUser->account->Id ?? null;

        $q = Expenses::query()
            ->with('category')
            ->where('AccountId', $accountId)
            ->orderByDesc('Id');

        // Filter only when user selects category
        if ($request->filled('category_id')) {
            $q->where('CategoryId', $request->category_id);
        }

        // Apply date filter only if user explicitly selects both
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->toDateString();
            $end   = Carbon::parse($request->end_date)->toDateString();

            $q->whereDate('PaidDateTime', '>=', $start)
              ->whereDate('PaidDateTime', '<=', $end);
        }

        // Apply search if present
        if ($request->filled('search')) {
            $term = $request->search;
            $q->where(function ($sub) use ($term) {
                $sub->where('Supplier', 'like', "%{$term}%")
                    ->orWhere('Notes', 'like', "%{$term}%");
            });
        }

        return $q->paginate(20);
    }

    public function find($id)
    {
        return Expenses::with('category')->findOrFail($id);
    }


    public function store(array $data, $user)
    {
        $account = $user->bkUser->account;
        $user = $user->bkUser;
        $next_execution_date = null;

        if (!$account) {
            return null;
        }

        $accountPeriod = AccountingPeriod::where('AccountId', $account->Id)->first();

        if (!empty($data['recurring'])) {
            $paidDate = Carbon::parse($data['paid_date_time']);
            switch ($data['recurring']) {
                case 'weekly': $next_execution_date = $paidDate->copy()->addWeeks(1); break;
                case 'fortnightly': $next_execution_date = $paidDate->copy()->addWeeks(2); break;
                case '4_weeks': $next_execution_date = $paidDate->copy()->addWeeks(4); break;
                case 'monthly': $next_execution_date = $paidDate->copy()->addDay()->addMonth()->subDay(); break;
            }
        }

        $expenseId = DB::table('Expenses')->insertGetId([
            'AccountId' => $account->Id,
            'Supplier' => $data['supplier'],
            'AccountingPeriodId' => @$accountPeriod->Id,
            'Amount' => $data['amount'],
            'CategoryId' => $data['category_id'],
            'Notes' => $data['notes'] ?? null,
            'ReciptId' => $data['receipt_id'] ?? null,
            'parentId' => 0,
            'PaymentMethod' => $data['payment_method'] == 'Cash' ? 0 : 1,
            'PaidDateTime' => $data['paid_date_time'],
            'recurring' => $data['recurring'] ?? null,
            'next_execution_date' => $next_execution_date,
            'recurring_created_at' => Carbon::now(),
            'DateCreated' => Carbon::now(),
            'CreatedById' => @$user->Id,
        ]);

        $expense = Expenses::find($expenseId);

        if (!empty($data['recurring']) && $expense) {
            $execution_date = Carbon::parse($expense->next_execution_date);
            $now = Carbon::now();

            while ($execution_date->lessThanOrEqualTo($now)) {
                $execution_date = $this->recurringExpense($expense, $execution_date);
                $expense->refresh();
            }
        }

        return $expense;
    }

    /**
     * Create a child recurring expense and update parent's next_execution_date
     */
    private function recurringExpense($expense, $execution_date)
    {
        DB::table('Expenses')->insertGetId([
            'AccountId' => $expense->AccountId,
            'Supplier'  => $expense->Supplier,
            'AccountingPeriodId' => @$expense->AccountingPeriodId,
            'Amount' => $expense->Amount,
            'CategoryId' => $expense->CategoryId,
            'Notes' => $expense->Notes,
            'ReciptId' => null,
            'parentId' => $expense->Id,
            'PaymentMethod' => $expense->PaymentMethod,
            'PaidDateTime' => Carbon::parse($execution_date),
            'recurring' => $expense->recurring,
            'recurring_created_at' => $execution_date,
            'next_execution_date' => null,
            'DateCreated' => $execution_date,
            'CreatedById' => @$expense->CreatedById]);
        // Update Next Execution Date of Parent
        if($expense->recurring == 'weekly'){
            $next_execution_date = Carbon::parse($execution_date)->addWeeks(1);
        }
        if($expense->recurring == 'fortnightly'){
            $next_execution_date = Carbon::parse($execution_date)->addWeeks(2);
        }
        if($expense->recurring == '4_weeks'){
            $next_execution_date = Carbon::parse($execution_date)->addWeeks(4);
        }
        if($expense->recurring == 'monthly'){
            $next_execution_date = Carbon::parse($execution_date)->addDay()->addMonth()->subDay();
        }
        DB::table('Expenses')->where('Id',$expense->Id)->update(['next_execution_date'=>$next_execution_date]);

        DB::table('recurring_expense_logs')->insertGetId([
            'AccountId' => $expense->AccountId,
            'Supplier'  => $expense->Supplier,
            'AccountingPeriodId' => @$expense->AccountingPeriodId,
            'Amount' => $expense->Amount,
            'CategoryId' => $expense->CategoryId,
            'Notes' => $expense->Notes,
            'ReciptId' => $expense->ReciptId,
            'PaymentMethod' => $expense->PaymentMethod,
            'PaidDateTime' => date("Y-m-d",strtotime(Carbon::parse($execution_date)->subDay())),  // to show correct date on App Side
            'recurring' => $expense->recurring,
            'recurring_created_at' => date('Y-m-d', strtotime($execution_date)),
            'DateModified' => date('Y-m-d',strtotime($execution_date)),
            'ModifiedById' => @$expense->CreatedById,
            'DateCreated' => date('Y-m-d',strtotime($execution_date)),
            'CreatedById' => @$expense->CreatedById]);
            return $next_execution_date;
    }


    public function update($id, array $data)
    {
        $expense = Expenses::findOrFail($id);

        $next_execution_date = $expense->next_execution_date;

        if (!empty($data['recurring'])) {
            $paidDate = Carbon::parse($data['paid_date_time']);
            switch ($data['recurring']) {
                case 'weekly': $next_execution_date = $paidDate->copy()->addWeeks(1); break;
                case 'fortnightly': $next_execution_date = $paidDate->copy()->addWeeks(2); break;
                case '4_weeks': $next_execution_date = $paidDate->copy()->addWeeks(4); break;
                case 'monthly': $next_execution_date = $paidDate->copy()->addDay()->addMonth()->subDay(); break;
            }
        }

        $expense->update([
            'Supplier' => $data['supplier'],
            'Amount' => $data['amount'],
            'CategoryId' => $data['category_id'],
            'Notes' => $data['notes'] ?? null,
            'ReciptId' => $data['receipt_id'] ?? null,
            'PaymentMethod' => $data['payment_method'] == 'Cash' ? 0 : 1,
            'PaidDateTime' => $data['paid_date_time'],
            'recurring' => $data['recurring'] ?? null,
            'next_execution_date' => $next_execution_date,
            'DateModified' => now(),
        ]);

        return $expense;
    }

    public function delete($id)
    {
        return Expenses::where('Id', $id)->delete();
    }
}
