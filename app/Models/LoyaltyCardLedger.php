<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCardLedger extends Model
{
    protected $fillable = ['account_id','customer_id','stamps','current_tier'];
}
