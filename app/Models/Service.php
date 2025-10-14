<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'Services';

    protected $fillable = [
        'AccountId',
        'Name',
        'TotalPrice',
        'DepositType',
        'Deposit',
        'DefaultAppointmentDuration',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'AccountId', 'Id');
    }

    public function incomes()
    {
        return $this->hasMany(Income::class, 'ServiceId');
    }
}
