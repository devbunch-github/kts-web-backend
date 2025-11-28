<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardUsage extends Model
{
    protected $table = 'gift_card_usages';

    protected $fillable = [
        'gift_card_purchase_id',
        'appointment_id',
        'customer_id',
        'account_id',
        'used_amount',
    ];

    public function purchase()
    {
        return $this->belongsTo(GiftCardPurchase::class, 'gift_card_purchase_id', 'Id');
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
