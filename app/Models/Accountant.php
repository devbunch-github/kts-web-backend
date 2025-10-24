<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accountant extends Model
{
    use SoftDeletes;

    protected $fillable = [
    'AccountId', 'created_by', 'name', 'email', 'password', 'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account() {
        return $this->belongsTo(Account::class, 'AccountId', 'Id');
    }
}
