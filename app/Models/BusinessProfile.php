<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'AccountId',
        'phone_number',
        'image_url',
    ];

    protected $casts = [
        'AccountId' => 'integer',
    ];
}
