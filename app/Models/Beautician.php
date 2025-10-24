<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Beautician extends Model
{
    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'country',
        'city',
        'address',
        'services',
        'logo',
        'cover',
        'location',
        'category',
        'rating',
        'reviews_count',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    protected $appends = ['logo_url', 'cover_url'];

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return null;
        }

        // Return full absolute URL
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo; // already full URL
        }

        return URL::to(Storage::url($this->logo));
    }

    public function getCoverUrlAttribute()
    {
        if (!$this->cover) {
            return null;
        }

        if (filter_var($this->cover, FILTER_VALIDATE_URL)) {
            return $this->cover;
        }

        return URL::to(Storage::url($this->cover));
    }
}
