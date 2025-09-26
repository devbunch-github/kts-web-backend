<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable=['name','price_minor','currency','features','is_active'];
    protected $casts=['features'=>'array','is_active'=>'bool'];
}
