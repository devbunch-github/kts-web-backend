<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id','payment_provider','provider_payment_id',
        'amount','currency','paid_at','status'
    ];
}
