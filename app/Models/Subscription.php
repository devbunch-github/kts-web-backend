<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id', 'plan_id', 'status', 'payment_provider',
        'payment_reference', 'starts_at', 'ends_at'
    ];

    protected $dates = ['starts_at', 'ends_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * The user who owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The plan associated with the subscription.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

}
