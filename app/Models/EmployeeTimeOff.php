<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeTimeOff extends Model
{
    use HasFactory;

    protected $fillable = [
        'AccountId','employee_id','date','start_time','end_time',
        'is_repeat','repeat_until','note','recurrence_id'
    ];

    protected $casts = [
        'date'=>'date',
        'repeat_until'=>'date',
        'is_repeat'=>'boolean'
    ];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }
}
