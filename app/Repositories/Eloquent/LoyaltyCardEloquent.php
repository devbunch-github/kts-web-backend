<?php

namespace App\Repositories\Eloquent;

use App\Models\LoyaltyCardSetting;
use App\Repositories\Contracts\LoyaltyCardRepository;
use Illuminate\Support\Facades\DB;

class LoyaltyCardEloquent implements LoyaltyCardRepository
{
    public function getByAccount(int $accountId): ?LoyaltyCardSetting {
        return LoyaltyCardSetting::with('tiers')->where('account_id', $accountId)->first();
    }

    public function upsert(int $accountId, array $data): LoyaltyCardSetting {
        return DB::transaction(function () use ($accountId, $data) {
            $setting = LoyaltyCardSetting::updateOrCreate(
                ['account_id' => $accountId],
                [
                    'is_enabled' => (bool)($data['is_enabled'] ?? false),
                    'min_purchase_amount' => $data['min_purchase_amount'] ?? 0,
                    'tiers_per_card' => $data['tiers_per_card'] ?? 1,
                    'stamps_per_tier' => $data['stamps_per_tier'] ?? 3,
                ]
            );

            if (isset($data['tiers']) && is_array($data['tiers'])) {
                $setting->tiers()->delete();
                foreach ($data['tiers'] as $i => $tier) {
                    $setting->tiers()->create([
                        'tier_number' => $i + 1,
                        'reward_type' => $tier['reward_type'],
                        'reward_value' => $tier['reward_value'],
                    ]);
                }
            }
            return $setting->load('tiers');
        });
    }
}
