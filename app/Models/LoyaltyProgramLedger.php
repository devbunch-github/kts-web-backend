<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyProgramLedger extends Model
{
    protected $fillable = ['account_id','customer_id','points_balance'];
}
