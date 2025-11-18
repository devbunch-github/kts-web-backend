<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class AccountDetailsSheet implements FromCollection, WithTitle
{
    protected $account_details;

    public function __construct(array $account_details)
    {
        $this->account_details = $account_details;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $exportData = [];

        $exportData[] = array_keys($this->account_details);
        $exportData[] = array_values($this->account_details);
        return collect($exportData);
    }

    public function title(): string
    {
        return 'Account Details';
    }
}