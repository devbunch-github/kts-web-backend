<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'AccountId','title','frequency','is_active','created_by','updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(BusinessFormQuestion::class, 'form_id')->orderBy('sort_order');
    }

    public function services()
    {
        // your Services model table is usually `services`
        return $this->belongsToMany(Service::class, 'business_form_service', 'form_id', 'service_id')->withTimestamps();
    }
}
