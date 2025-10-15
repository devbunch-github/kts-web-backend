<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'Name' => ['sometimes','required','string','max:255'],
            'Description' => ['sometimes','nullable','string'],
            'IsActive' => ['sometimes','boolean'],
        ];
    }
}
