<?php

namespace App\Repositories\Eloquent;

use App\Models\LoyaltyProgramLedger;
use App\Models\LoyaltyProgramSetting;
use App\Repositories\Contracts\LoyaltyProgramRepository;
use Illuminate\Support\Facades\DB;

class LoyaltyProgramEloquent implements LoyaltyProgramRepository
{
    public function getByAccount(int $accountId): ?LoyaltyProgramSetting {
        return LoyaltyProgramSetting::with('services')->where('account_id', $accountId)->first();
    }

    public function upsert(int $accountId, array $data): LoyaltyProgramSetting {
        return DB::transaction(function () use ($accountId, $data) {
            $setting = LoyaltyProgramSetting::updateOrCreate(
                ['account_id' => $accountId],
                [
                    'is_enabled' => (bool)($data['is_enabled'] ?? false),
                    'points_per_currency' => (int)($data['points_per_currency'] ?? 1),
                    'points_per_redemption_currency' => (int)($data['points_per_redemption_currency'] ?? 50),
                ]
            );

            if (array_key_exists('service_ids', $data)) {
                $setting->services()->sync($data['service_ids'] ?: []);
            }

            return $setting->load('services');
        });
    }

    public function summary(int $accountId): array {
        $outstandingCustomers = LoyaltyProgramLedger::where('account_id', $accountId)
            ->where('points_balance', '>', 0)->count();

        $totalOutstandingPoints = LoyaltyProgramLedger::where('account_id', $accountId)
            ->sum('points_balance');

        $setting = $this->getByAccount($accountId);
        $valuePerPoint = $setting && $setting->points_per_redemption_currency > 0
            ? (1 / $setting->points_per_redemption_currency)
            : 0;

        return [
            'outstanding_total' => $outstandingCustomers,
            'total_outstanding_value' => round($totalOutstandingPoints * $valuePerPoint, 2),
            'total_credits_value' => 0.00, // placeholder if you run a separate credits pool
        ];
    }
}
