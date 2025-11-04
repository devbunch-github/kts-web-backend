<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountEmailTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'subject'  => 'sometimes|required|string|max:255',
            'body'     => 'sometimes|required|string',
            'status'   => 'sometimes|boolean',
            'logo_url' => 'nullable|url',
        ];
    }
}
