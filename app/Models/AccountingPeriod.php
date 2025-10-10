<?php

// app/Models/AccountingPeriod.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
     protected $connection = 'sqlsrv';
    protected $table = 'AccountingPeriods';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $fillable = [
        'AccountId',
        'StartDateTime',
        'EndDateTime',
        'AnnualEmploymentIncome',
        'CreatedById',
        'DateCreated',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->DateCreated = $model->DateCreated ?? now();
        });
    }
}
