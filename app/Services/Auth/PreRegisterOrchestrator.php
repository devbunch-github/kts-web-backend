<?php

// app/Services/Auth/PreRegisterOrchestrator.php
namespace App\Services\Auth;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Repositories\Eloquent\BkUserRepository;
use App\Repositories\Eloquent\AccountRepository;
use App\Repositories\Eloquent\AccountingPeriodRepository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PreRegisterOrchestrator
{
    public function __construct(
        protected BkUserRepository $bkUsers,
        protected AccountRepository $accounts,
        protected AccountingPeriodRepository $periods,
    ) {}

    /**
     * Called right after your existing preRegister finishes creating the Laravel user.
     */
    public function afterPreRegister(User $user): array
    {
        return DB::transaction(function () use ($user) {
            // 1) BkUser (1:1 with business user)
            $bkUser = $this->bkUsers->create([
                'IdentityId' => (string) Str::uuid(),
                'Email'      => $user->email,
                'DisplayName'=> $user->name ?? $user->email,
                'CreatedById' => $user->id ?? null,
            ]);

            // 2) Account (ties to BkUser)
            $account = $this->accounts->create([
                'Name'             => $user->name ?? 'New Account',
                'UserId'           => $bkUser->Id,
                'IsTestAccount'    => true,
                'AnnualEmploymentIncome' => 0,
                'SubscriptionType' => false,
                'AppStoreUserId' => false,
            ]);

            // 3) Initial Accounting Period (current month, since you’ll manage manually later)
            $period = $this->periods->create([
                'AccountId'               => $account->Id,
                'StartDateTime'           => now()->startOfMonth(),
                'EndDateTime'             => now()->endOfMonth(),
                'AnnualEmploymentIncome'  => 0,
                'CreatedById'             => $bkUser->Id,
            ]);

            return compact('bkUser','account','period');
        });
    }
}
