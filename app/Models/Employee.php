<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'AccountId', 'name', 'title', 'phone', 'email', 'image',
        'start_date', 'start_year', 'end_date', 'end_year',
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'employee_service');
    }

    public function timeOffs()
    {
        return $this->hasMany(EmployeeTimeOff::class);
    }
}
