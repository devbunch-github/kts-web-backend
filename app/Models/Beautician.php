<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beautician extends Model
{
    protected $fillable = [
        'name',
        'location',
        'category',
        'rating',
        'reviews_count',
    ];
}
