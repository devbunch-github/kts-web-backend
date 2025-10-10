<?php

// app/Repositories/AccountRepository.php
namespace App\Repositories\Eloquent;

use App\Models\Account;

class AccountRepository
{
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    public function forBkUserId(int $bkUserId): ?Account
    {
        return Account::where('UserId',$bkUserId)->first();
    }
}
