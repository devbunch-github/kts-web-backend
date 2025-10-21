<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentSetting extends Model
{
    protected $fillable = [
        'user_id',
        'AccountId',
        'paypal_active',
        'paypal_client_id',
        'paypal_client_secret',
        'paypal_email',
        'stripe_active',
        'stripe_public_key',
        'stripe_secret_key',
        'pay_at_venue',
    ];

    protected $casts = [
        'paypal_active' => 'boolean',
        'stripe_active' => 'boolean',
        'pay_at_venue' => 'boolean',
    ];

    /** Automatically encrypt sensitive attributes */
    public function setPaypalClientIdAttribute($value)
    {
        $this->attributes['paypal_client_id'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    public function getPaypalClientIdAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setPaypalClientSecretAttribute($value)
    {
        $this->attributes['paypal_client_secret'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    public function getPaypalClientSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setStripePublicKeyAttribute($value)
    {
        $this->attributes['stripe_public_key'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    public function getStripePublicKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setStripeSecretKeyAttribute($value)
    {
        $this->attributes['stripe_secret_key'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    public function getStripeSecretKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
