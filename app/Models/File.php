<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    protected $table = 'File';
    // protected $primaryKey = 'Id';
    protected $fillable = ['Id','Name','Identifier','DateCreated','DateModified','CreatedById','ModifiedById','receipt_id'];
    protected $casts = [
        'id' => 'integer',
    ];
}