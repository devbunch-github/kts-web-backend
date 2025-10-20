<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'image' => 'nullable|string',
            'start_date' => 'nullable|date',
            'start_year' => 'nullable|integer',
            'end_date' => 'nullable|date',
            'end_year' => 'nullable|integer',
            'service_ids' => 'array',
        ];
    }
}
