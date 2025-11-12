<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class IncomeSheet implements FromCollection, WithTitle
{
    protected $incomes;

    public function __construct(array $incomes)
    {
        $this->incomes = $incomes;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $exportData = [];

        $exportData[] = ['Payment Date/Time', 'Payment Method','Amount','Description'];

        foreach ($this->incomes as $income) {
            $exportData[] = [
                date('Y-m-d H:i:s',strtotime($income['paymentDateTime'])),
                $income['paymentMethod'],
                $income['amount'],
                $income['description'],
            ];
        }

        return collect($exportData);
    }

    public function title(): string
    {
        return 'Income';
    }
}