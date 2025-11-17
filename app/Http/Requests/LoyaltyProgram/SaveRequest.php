<?php

namespace App\Http\Requests\LoyaltyProgram;

use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'is_enabled' => 'required|boolean',
            'points_per_currency' => 'required|integer|min:0',
            'points_per_redemption_currency' => 'required|integer|min:1',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
        ];
    }
}
