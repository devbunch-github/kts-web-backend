<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = 'Appointments';
    protected $primaryKey = 'Id';

    protected $fillable = [
        'StartDateTime',
        'EndDateTime',
        'Cost',
        'Deposit',
        'CustomerId',
        'ServiceId',
        'AccountId',
        'Status',
        'CancellationDate',
        'Tip',
        'RefundAmount',
        'Discount',
        'FinalAmount',
        'DateCreated',
        'EmployeeId',
        'DateModified',
        'CreatedById',
        'ModifiedById'
    ];

    protected $casts = [
        'StartDateTime' => 'datetime',
        'EndDateTime' => 'datetime',
        'CancellationDate' => 'datetime',
    ];

    public $timestamps = false;


    // RELATIONS
    public function customer() {
        return $this->belongsTo(Customer::class, 'CustomerId');
    }

    public function service() {
        return $this->belongsTo(Service::class, 'ServiceId');
    }

    public function account() {
        return $this->belongsTo(Account::class, 'AccountId');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'EmployeeId');
    }

}
