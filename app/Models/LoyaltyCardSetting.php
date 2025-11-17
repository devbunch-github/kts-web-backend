<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCardSetting extends Model
{
    protected $fillable = [
        'account_id','is_enabled','min_purchase_amount','tiers_per_card','stamps_per_tier'
    ];

    public function tiers() {
        return $this->hasMany(LoyaltyCardTier::class);
    }
}
