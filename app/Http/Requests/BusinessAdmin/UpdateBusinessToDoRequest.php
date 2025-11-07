<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessToDoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'due_datetime' => ['nullable', 'date'],
            'is_completed' => ['nullable', 'boolean'],
        ];
    }
}
