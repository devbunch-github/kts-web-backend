<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyProgramTransaction extends Model
{
    protected $fillable = [
        'account_id','customer_id','type','points','currency_value','reference_type','reference_id'
    ];
}
