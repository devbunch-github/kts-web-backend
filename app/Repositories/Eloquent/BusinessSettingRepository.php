<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BusinessSettingRepositoryInterface;
use App\Models\BusinessSetting;

class BusinessSettingRepository implements BusinessSettingRepositoryInterface
{
    public function findByType(int $accountId, string $type)
    {
        return BusinessSetting::where('account_id', $accountId)
            ->where('type', $type)
            ->first();
    }

    public function saveOrUpdate(int $accountId, string $type, array $data)
    {
        return BusinessSetting::updateOrCreate(
            ['account_id' => $accountId, 'type' => $type],
            ['data' => $data]
        );
    }
}

