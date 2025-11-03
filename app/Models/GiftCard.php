<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'service_id',
        'code',
        'title',
        'discount_type',
        'discount_amount',
        'start_date',
        'end_date',
        'notes',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'Id');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image_path) return null;
        return asset('storage/' . $this->image_path);
    }
}
