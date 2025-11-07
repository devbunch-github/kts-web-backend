<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessToDo extends Model
{
    use SoftDeletes;

    protected $table   = 'business_todos';
    protected $guarded = [];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_datetime' => 'datetime',
    ];
}
