<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'supplier' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,Id',
            'notes' => 'nullable|string',
            'payment_method' => 'required|string|in:Cash,Bank',
            'paid_date_time' => 'required|date',
            'recurring' => 'nullable|string|in:weekly,fortnightly,4_weeks,monthly',
            'receipt_id' => 'nullable|integer|exists:File,Id',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

}
