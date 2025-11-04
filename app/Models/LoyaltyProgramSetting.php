<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyProgramSetting extends Model
{
    protected $fillable = [
        'account_id','is_enabled','points_per_currency','points_per_redemption_currency'
    ];

    public function services() {
        return $this->belongsToMany(Service::class, 'loyalty_program_service', 'loyalty_program_setting_id', 'service_id');
    }
}
