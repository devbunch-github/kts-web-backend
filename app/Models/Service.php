<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'Services';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'AccountId',
        'CategoryId',
        'Name',
        'TotalPrice',
        'DepositType',
        'Deposit',
        'DefaultAppointmentDuration',
        'Description',
        'FilePath',
        'ImagePath',
        'DateCreated',
        'DateModified',
        'CreatedById',
        'ModifiedById',
        'IsDeleted',
    ];

    protected $casts = [
        'TotalPrice' => 'decimal:2',
        'Deposit' => 'decimal:2',
        'IsDeleted' => 'boolean',
    ];

    public function account()  { return $this->belongsTo(Account::class,'AccountId','Id'); }
    public function category() { return $this->belongsTo(Category::class,'CategoryId','Id'); }
    public function incomes()  { return $this->hasMany(Income::class,'ServiceId'); }
}
