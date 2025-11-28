<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCodeUsage extends Model
{
    protected $table = 'promo_code_usages';

    protected $fillable = [
        'promo_code_id',
        'customer_id',
        'appointment_id',
        'account_id',
        'used_amount',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'Id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'Id');
    }
}
