<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessFormSubmission extends Model
{
    protected $fillable = ['AccountId','form_id','appointment_id','customer_id','submitted_at'];

    protected $dates = ['submitted_at'];

    public function answers()
    {
        return $this->hasMany(BusinessFormAnswer::class, 'submission_id');
    }
}
