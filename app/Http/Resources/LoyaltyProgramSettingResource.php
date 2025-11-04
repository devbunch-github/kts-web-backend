<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyProgramSettingResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'is_enabled' => (bool)$this->is_enabled,
            'points_per_currency' => (int)$this->points_per_currency,
            'points_per_redemption_currency' => (int)$this->points_per_redemption_currency,
            'service_ids' => $this->whenLoaded('services', fn() => $this->services->pluck('id')),
        ];
    }
}
