<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Customers';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Name',
        'MobileNumber',
        'Email',
        'AccountId',
        'DateOfBirth',
        'Note',
        'DateCreated',
        'DateModified',
        'CreatedById',
        'ModifiedById',
        'is_deleted',
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

    // Optional relationships
    public function account()
    {
        return $this->belongsTo(Account::class, 'AccountId', 'Id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'CreatedById', 'id');
    }
}
