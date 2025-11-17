<?php

namespace App\Http\Requests\BusinessAdmin\Rota;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeOffRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'employee_id' => ['required','integer','exists:employees,id'],
            'date'        => ['required','date'],
            'start_time'  => ['required','date_format:H:i'],
            'end_time'    => ['required','date_format:H:i','after:start_time'],
            'repeat'      => ['nullable','boolean'],
            'repeat_until'=> ['required_if:repeat,1','nullable','date','after_or_equal:date'],
            'note'        => ['nullable','string','max:2000'],
        ];
    }
}
