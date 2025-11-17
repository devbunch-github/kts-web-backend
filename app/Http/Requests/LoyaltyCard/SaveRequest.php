<?php

namespace App\Http\Requests\LoyaltyCard;

use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'is_enabled' => 'required|boolean',
            'min_purchase_amount' => 'required|numeric|min:0',
            'tiers_per_card' => 'required|integer|min:1|max:5',
            'stamps_per_tier' => 'required|integer|min:3|max:6',
            'tiers' => 'required|array|size:' . $this->input('tiers_per_card', 1),
            'tiers.*.reward_type' => 'required|in:percentage,fixed',
            'tiers.*.reward_value' => 'required|numeric|min:0',
        ];
    }
}
