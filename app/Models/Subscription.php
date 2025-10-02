<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable=['user_id','plan_id','status','payment_provider','payment_reference','starts_at','ends_at'];
    protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime'];
}
