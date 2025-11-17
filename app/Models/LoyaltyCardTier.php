<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCardTier extends Model
{
    protected $fillable = [
        'loyalty_card_setting_id','tier_number','reward_type','reward_value'
    ];
}
