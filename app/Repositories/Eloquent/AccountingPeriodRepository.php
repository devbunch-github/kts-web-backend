<?php

// app/Repositories/AccountingPeriodRepository.php
namespace App\Repositories\Eloquent;

use App\Models\AccountingPeriod;

class AccountingPeriodRepository
{
    public function create(array $data): AccountingPeriod
    {
        return AccountingPeriod::create($data);
    }

    public function latestForAccount(int $accountId): ?AccountingPeriod
    {
        return AccountingPeriod::where('AccountId',$accountId)
            ->orderByDesc('StartDateTime')->first();
    }
}
