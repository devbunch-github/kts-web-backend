<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class GiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'title' => 'required|string|max:191',
            'service_id' => 'nullable|exists:services,Id',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
        ];
    }
}
