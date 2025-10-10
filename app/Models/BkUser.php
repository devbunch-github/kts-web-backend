<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BkUser extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'BkUsers';
    public $timestamps = false;
    protected $primaryKey = 'Id';

    protected $fillable = [
        'IdentityId',
        'Email',
        'DisplayName',
        'DateCreated',
        'CreatedById',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->DateCreated = $model->DateCreated ?? now();
            // $model->CreatedById = $model->CreatedById ?? (Auth::id() ?? 0);
        });
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'UserId', 'Id');
    }

}
