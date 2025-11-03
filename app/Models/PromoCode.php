<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use SoftDeletes;

    public $timestamps = false; // we maintain date_created/date_modified

    protected $fillable = [
        'account_id','created_by_id','modified_by_id',
        'title','code','service_id','discount_type','discount_value',
        'start_date','end_date','status','notes','date_created','date_modified'
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'status'       => 'integer',
        'discount_value'=> 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function scopeForAccount($q, int $accountId)
    {
        return $q->where('account_id', $accountId);
    }

    public function getIsActiveAttribute(): bool
    {
        $today = now()->startOfDay();
        $withinDates = $this->start_date?->startOfDay() <= $today
            && (is_null($this->end_date) || $this->end_date->endOfDay() >= $today);

        return $this->status === 1 && $withinDates;
    }
}
