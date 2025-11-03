<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountEmailTemplate extends Model
{
    protected $fillable = [
        'account_id', 'email_template_id', 'subject', 'body', 'status', 'logo_url'
    ];

    protected $casts = ['status' => 'boolean'];

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'Id');
    }

    public function getEffectiveSubjectAttribute()
    {
        return $this->subject ?? $this->emailTemplate?->subject ?? '';
    }

    public function getEffectiveBodyAttribute()
    {
        return $this->body ?? $this->emailTemplate?->body ?? '';
    }
}
