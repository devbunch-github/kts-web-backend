<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ServiceUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'Name' => ['sometimes','required','string','max:255'],
            'CategoryId' => ['sometimes','nullable','integer','exists:categories,Id'],
            'TotalPrice' => ['sometimes','required','numeric','min:0'],
            'DepositType' => ['sometimes','nullable'],
            'Deposit' => ['sometimes','nullable','numeric','min:0'],
            'DefaultAppointmentDuration' => ['sometimes','required','integer','min:1'],
            'DurationUnit' => ['sometimes', 'nullable', 'in:mins,hours'],
            'Description' => ['sometimes','nullable','string'],
            'FilePath' => ['sometimes','nullable','string','max:255'],
            'ImagePath' => ['sometimes','nullable','string','max:255'],
        ];
    }
}
