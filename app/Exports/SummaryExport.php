<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SummaryExport implements WithMultipleSheets
{
    use Exportable;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Each sheet class should exist in app/Exports/Sheets/
        $sheets[] = new AccountDetailsSheet(
            collect($this->data['account'])->except([
                'Id',
                'County',
                'Utr',
                'SubscriptionType',
                'UserId',
                'IsTestAccount',
                'DateCreated',
                'DateModified',
                'CreatedById',
                'ModifiedById',
            ])->toArray()
        );

        $sheets[] = new IncomeSheet($this->data['incomes']->toArray());
        // $sheets[] = new TipSheet($this->data['tips']->toArray());
        $sheets[] = new ExpenseSheet($this->data['expenses']);
        // $sheets[] = new PensionSheet($this->data['pensions']->toArray());
        // $sheets[] = new DrawingSheet($this->data['drawings']->toArray());
        // $sheets[] = new LoanSheet($this->data['loan']->toArray());

        return $sheets;
    }
}
