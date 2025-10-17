<?php

namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'CustomerId'     => 'required|exists:Customers,id',
            'ServiceId'      => 'required|exists:Services,id',
            'StartDateTime'  => 'required|date',
            'EndDateTime'    => 'nullable|date|after:StartDateTime',
            'Cost'           => 'required|numeric|min:0',
            'Deposit'        => 'nullable|numeric|min:0',
            'Tip'            => 'nullable|numeric|min:0',
            'RefundAmount'   => 'nullable|numeric|min:0',
            'Status'         => 'required',
            'EmployeeId'    => 'nullable|integer|exists:Employees,Id',
        ];
    }
}
