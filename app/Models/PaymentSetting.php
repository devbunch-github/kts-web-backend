<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = [
        'user_id',
        'paypal_client_id',
        'paypal_client_secret',
        'paypal_email',
        'stripe_public_key',
        'stripe_secret_key',
    ];
}