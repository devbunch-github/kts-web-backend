<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessEmailTemplate extends Model
{
    protected $fillable = [
        'business_id', 'email_template_id', 'subject', 'body', 'status', 'logo_url'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Effective values (fallback to master if override not present)
    public function getEffectiveSubjectAttribute(): string
    {
        return $this->subject ?? $this->emailTemplate?->subject ?? '';
    }

    public function getEffectiveBodyAttribute(): string
    {
        return $this->body ?? $this->emailTemplate?->body ?? '';
    }
}
