<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyCardSettingResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'is_enabled' => (bool)$this->is_enabled,
            'min_purchase_amount' => (float)$this->min_purchase_amount,
            'tiers_per_card' => (int)$this->tiers_per_card,
            'stamps_per_tier' => (int)$this->stamps_per_tier,
            'tiers' => $this->whenLoaded('tiers', fn() => $this->tiers->map(fn($t) => [
                'tier_number' => $t->tier_number,
                'reward_type' => $t->reward_type,
                'reward_value' => (float)$t->reward_value,
            ])->values()),
        ];
    }
}
