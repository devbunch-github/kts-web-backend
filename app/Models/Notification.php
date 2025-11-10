<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'Notifications';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'AccountId',
        'Header',
        'Message',
        'ReadDateTime',
        'Discriminator',
    ];
}
