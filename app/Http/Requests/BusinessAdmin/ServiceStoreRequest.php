<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ServiceStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'Name' => ['required','string','max:255'],
            'CategoryId' => ['nullable','integer','exists:categories,Id'],
            'TotalPrice' => ['required','numeric','min:0'],
            'DepositType' => ['nullable'],
            'Deposit' => ['nullable','numeric','min:0'],
            'DefaultAppointmentDuration' => ['required','integer','min:1'],
            'Description' => ['nullable','string'],
            'FilePath' => ['nullable','string','max:255'],
            'ImagePath' => ['nullable','string','max:255'],
        ];
    }
}
