<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsPackage extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'total_sms', 'price', 'description'];
}