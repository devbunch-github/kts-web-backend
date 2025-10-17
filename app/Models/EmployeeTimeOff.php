<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeTimeOff extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'date', 'start_time', 'end_time', 'is_repeat', 'repeat_until', 'note',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
