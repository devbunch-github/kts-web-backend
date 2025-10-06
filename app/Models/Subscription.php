<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'status', 'payment_provider',
        'payment_reference', 'starts_at', 'ends_at'
    ];

    protected $dates = ['starts_at', 'ends_at'];

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}
