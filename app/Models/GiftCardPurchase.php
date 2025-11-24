<?php

// app/Models/GiftCardPurchase.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
    ];

    protected $casts = [
        'Amount'    => 'decimal:2',
        'ExpiresAt' => 'datetime',
        'PaidAt'    => 'datetime',
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
}
