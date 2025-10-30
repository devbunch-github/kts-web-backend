<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeEditLog extends Model
{
    protected $fillable = [
        'income_id',
        'edited_by',
        'field_name',
        'old_value',
        'new_value',
        'reason',
    ];
}
