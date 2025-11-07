<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessFormQuestion extends Model
{
    protected $fillable = ['form_id','type','label','required','sort_order','options'];

    protected $casts = [
        'required' => 'boolean',
        'options'  => 'array',
    ];

    public function form()
    {
        return $this->belongsTo(BusinessForm::class, 'form_id');
    }
}
