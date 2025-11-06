<?php

namespace App\Http\Requests\BusinessAdmin\Rota;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegularShiftsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'employee_id' => ['required','integer','exists:employees,id'],
            'start_date'  => ['required','date'],
            'end_date'    => ['required','date','after_or_equal:start_date'],
            'every_n_weeks' => ['required','integer','in:1,2,3,4'],
            'days' => ['required','array','min:1'],
            'days.*.day' => ['required','string'],
            'days.*.enabled' => ['required','boolean'],
            'days.*.start_time' => ['required_if:days.*.enabled,true','date_format:H:i'],
            'days.*.end_time' => ['required_if:days.*.enabled,true','date_format:H:i','after:days.*.start_time'],
            'note' => ['nullable','string','max:2000'],
        ];
    }
}
