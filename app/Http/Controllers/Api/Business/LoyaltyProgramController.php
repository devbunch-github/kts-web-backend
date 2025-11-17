<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoyaltyProgram\SaveRequest;
use App\Http\Resources\LoyaltyProgramSettingResource;
use App\Services\LoyaltyProgramService;
use Illuminate\Http\Request;
use App\Models\LoyaltyProgramSetting;
use Exception;

class LoyaltyProgramController extends Controller
{
    public function __construct(private LoyaltyProgramService $svc) {}

    protected function currentAccountId(): int
    {
        // mirror your PromoCode pattern exactly
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function show() {
        $accId = $this->currentAccountId();
        $setting = $this->svc->getSettings($accId);
        return new LoyaltyProgramSettingResource($setting ?? new LoyaltyProgramSetting([
            'account_id'=>$accId,'is_enabled'=>false,'points_per_currency'=>1,'points_per_redemption_currency'=>50
        ]));
    }

    public function save(SaveRequest $request) {
        $accId = $this->currentAccountId();
        $saved = $this->svc->saveSettings($accId, $request->validated());
        return (new LoyaltyProgramSettingResource($saved->load('services')))->additional(['message'=>'Saved']);
    }

    public function summary(Request $request) {
        $accId = $this->currentAccountId();
        return response()->json($this->svc->summary($accId));
    }
}
