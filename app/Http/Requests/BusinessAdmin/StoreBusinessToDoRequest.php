<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessToDoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'due_datetime' => ['nullable', 'date'],
        ];
    }
}
