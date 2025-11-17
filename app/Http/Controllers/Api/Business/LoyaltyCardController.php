<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoyaltyCard\SaveRequest;
use App\Http\Resources\LoyaltyCardSettingResource;
use App\Services\LoyaltyCardService;
use App\Models\LoyaltyCardSetting;
use Exception;

class LoyaltyCardController extends Controller
{
    public function __construct(private LoyaltyCardService $svc) {}

    protected function currentAccountId(): int
    {
        // mirror your PromoCode pattern exactly
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function show() {
        $accId = $this->currentAccountId();
        $setting = $this->svc->getSettings($accId);
        return new LoyaltyCardSettingResource($setting ?? tap(new LoyaltyCardSetting([
            'account_id'=>$accId,'is_enabled'=>false,'min_purchase_amount'=>0,'tiers_per_card'=>1,'stamps_per_tier'=>3
        ]))->setRelation('tiers', collect()));
    }

    public function save(SaveRequest $request) {
        $accId = $this->currentAccountId();
        $saved = $this->svc->saveSettings($accId, $request->validated());
        return (new LoyaltyCardSettingResource($saved))->additional(['message'=>'Saved']);
    }
}
