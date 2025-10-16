<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Expenses';
    public $timestamps = false;
    protected $primaryKey = 'Id';

    protected $fillable = [
        'AccountId',
        'Supplier',
        'Amount',
        'PaymentMethod',
        'PaidDateTime',
        'Notes',
        'DateCreated',
        'DateModified',
        'CreatedById',
        'ModifiedById',
        'Recurring',
        'Recurring_Created_At',
        'Next_Execution_Date',
        'ParentId',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'AccountId', 'Id');
    }
}