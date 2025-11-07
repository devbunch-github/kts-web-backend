<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessFormAnswer extends Model
{
    protected $fillable = ['submission_id','question_id','answer'];
}
