<?php

namespace App\Http\Requests\BusinessAdmin\Form;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessFormRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['required','string','max:255'],
            'frequency'    => ['required','in:once,every_booking'],
            'is_active'    => ['boolean'],
            'service_ids'  => ['array'],
            'service_ids.*'=> ['integer'],
            'questions'    => ['array'],
            'questions.*.id'        => ['nullable','integer'],
            'questions.*.type'      => ['required','in:short_answer,description,yes_no,checkbox'],
            'questions.*.label'     => ['required','string'],
            'questions.*.required'  => ['boolean'],
            'questions.*.sort_order'=> ['integer'],
            'questions.*.options'   => ['nullable','array'],
        ];
    }
}
