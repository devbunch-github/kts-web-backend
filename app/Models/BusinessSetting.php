<?php

// app/Models/BusinessSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = ['account_id', 'type', 'data'];
    protected $casts = ['data' => 'array'];
}
