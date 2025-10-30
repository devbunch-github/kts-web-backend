<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseEditLog extends Model
{
    protected $fillable = [
        'expense_id',
        'edited_by',
        'field_name',
        'old_value',
        'new_value',
        'reason',
    ];
}