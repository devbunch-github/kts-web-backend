<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'Id';
    public $timestamps = true;

    protected $fillable = [
        'Name', 'Description', 'AccountId','CreatedById','IsActive','updated_at','created_at',
    ];

    public function account()  { return $this->belongsTo(Account::class,'AccountId','Id'); }
    public function creator()  { return $this->belongsTo(User::class,'CreatedById'); }
    public function services() { return $this->hasMany(Service::class,'CategoryId','Id'); }
}
