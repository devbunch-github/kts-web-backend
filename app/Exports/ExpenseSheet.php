<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExpenseSheet implements FromCollection, WithTitle
{
    protected $expenses;

    public function __construct(array $expenses)
    {
        $this->expenses = $expenses;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $exportData = [];
        $exportData[] = ['Paid Date/Time', 'Payment Method', 'Supplier', 'Amount', 'Notes'];
        $exportData[] = ['', '', '', '', ''];

        foreach ($this->expenses as $cateory_name => $category_expense) {
            $exportData[] = [$cateory_name];
            foreach ($category_expense as $expense) {
                $exportData[] = [
                    date('Y-m-d H:i:s',strtotime($expense['paidDateTime'])),
                    $expense['paymentMethod'],
                    $expense['supplier'],
                    $expense['amount'],
                    $expense['notes'],
                ];
            }
            $exportData[] = ['', '', '', '', ''];
        }

        return collect($exportData);
    }

    public function title(): string
    {
        return 'Expenses';
    }
}
