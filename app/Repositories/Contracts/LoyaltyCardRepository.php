<?php

namespace App\Repositories\Contracts;

use App\Models\LoyaltyCardSetting;

interface LoyaltyCardRepository {
    public function getByAccount(int $accountId): ?LoyaltyCardSetting;
    public function upsert(int $accountId, array $data): LoyaltyCardSetting;
}
