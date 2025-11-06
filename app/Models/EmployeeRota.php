<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeRota extends Model
{
    use HasFactory;

    protected $fillable = [
        'AccountId','employee_id','shift_date','start_time','end_time',
        'source','recurrence_id','note','created_by'
    ];

    protected $casts = ['shift_date'=>'date'];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }
}
