<?php

namespace App\Repositories\Contracts;

use App\Models\LoyaltyProgramSetting;

interface LoyaltyProgramRepository {
    public function getByAccount(int $accountId): ?LoyaltyProgramSetting;
    public function upsert(int $accountId, array $data): LoyaltyProgramSetting;
    public function summary(int $accountId): array; // outstanding totals etc.
}
