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
            ->where('parentId', 0)
            ->orderByDesc('Id');

        if ($request->filled('category_id')) {
            $q->where('CategoryId', $request->category_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $q->whereBetween(DB::raw('DATE(PaidDateTime)'), [$request->start_date, $request->end_date]);
        }

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
        $accountPeriod = AccountingPeriod::where('AccountId', $account->Id ?? null)->first();

        $next_execution_date = null;

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
            'AccountId' => $account->Id ?? null,
            'Supplier'  => $data['supplier'],
            'AccountingPeriodId' => @$accountPeriod->Id,
            'Amount' => $data['amount'],
            'CategoryId' => $data['category_id'],
            'Notes' => $data['notes'] ?? null,
            'ReciptId' => $data['receipt_id'] ?? null,
            'PaymentMethod' => $data['payment_method'] == 'Cash' ? 0 : 1,
            'PaidDateTime' => $data['paid_date_time'],
            'recurring' => $data['recurring'] ?? null,
            'next_execution_date' => $next_execution_date,
            'recurring_created_at' => Carbon::now(),
            'parentId' => 0,
            'DateCreated' => Carbon::now(),
            'CreatedById' => @$user->Id
        ]);

        return Expenses::find($expenseId);
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
