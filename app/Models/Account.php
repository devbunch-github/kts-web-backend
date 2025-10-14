<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Accounts';
    public $timestamps = false;
    protected $primaryKey = 'Id';

    protected $fillable = [
        'Name',
        'UserId',
        'NationalInsuranceNumber',
        'AddressLine1',
        'AddressLine2',
        'Postcode',
        'Town',
        'County',
        'Country',
        'DateOfBirth',
        'Utr',
        'SubscriptionType',
        'AnnualEmploymentIncome',
        'AppStoreUserId',
        'IsTestAccount',
        'CreatedById',
        'DateCreated',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->DateCreated)) {
                $model->DateCreated = now();
            }
            if (empty($model->CreatedById)) {
                $model->CreatedById = auth()->id() ?? 0;
            }
        });
    }

    public function bkUser()
    {
        return $this->belongsTo(BkUser::class, 'UserId', 'Id');
    }

    public function user()
    {
        return $this->belongsTo(BkUser::class, 'UserId', 'Id');
    }

    public function incomes()
    {
        return $this->hasMany(Income::class, 'AccountId', 'Id');
    }


}