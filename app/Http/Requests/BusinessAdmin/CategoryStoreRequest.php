<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'Name' => ['required','string','max:255'],
            'Description' => ['nullable','string'],
            'IsActive' => ['sometimes','boolean'],
        ];
    }
}
