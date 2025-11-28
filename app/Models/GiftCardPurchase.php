<?php

// app/Models/GiftCardPurchase.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardPurchase extends Model
{
    protected $table = 'GiftCardPurchases';
    protected $primaryKey = 'Id';

    protected $fillable = [
        'GiftCardId',
        'CustomerId',
        'AccountId',
        'Code',
        'Amount',
        'PaymentMethod',
        'PaymentStatus',
        'StripeSessionId',
        'PayPalOrderId',
        'ExpiresAt',
        'PaidAt',
        'UsedAmount', // ⬅ add
    ];

    protected $casts = [
        'Amount'    => 'decimal:2',
        'ExpiresAt' => 'datetime',
        'PaidAt'    => 'datetime',
        'UsedAmount'=> 'decimal:2',
    ];

    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class, 'GiftCardId', 'Id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'CustomerId', 'Id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'AccountId', 'Id');
    }

    public function usages()
    {
        return $this->hasMany(GiftCardUsage::class, 'gift_card_purchase_id');
    }

    public function getRemainingAttribute()
    {
        return max(0, $this->Amount - $this->UsedAmount);
    }
}
