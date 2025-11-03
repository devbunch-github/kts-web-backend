<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['key', 'title', 'subject', 'body', 'default_status'];

    public function accountTemplates()
    {
        return $this->hasMany(AccountEmailTemplate::class);
    }
}
