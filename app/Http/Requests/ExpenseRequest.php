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
            'category_id' => 'required|integer',
            'payment_method' => 'required|string|in:Cash,Bank,Card',
            'paid_date_time' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'recurring' => 'nullable|in:weekly,fortnightly,4_weeks,monthly',
            'receipt_id' => 'nullable|integer'
        ];
    }
}
