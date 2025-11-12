<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Expenses;

class ExpenseCategory extends Model
{
    use HasFactory;
    protected $table = 'ExpenseCategory';
    // protected $primaryKey = 'Id';
    protected $fillable = ['Id','Name','HelpText','HelpUrl','PlaceHolderExampleText','DateCreated','DateModified','CreatedById','ModifiedById'];

    /**
     * Get the comments for the blog post.
     */
    public function expense(): HasMany
    {
        return $this->hasMany(Expenses::class);
    }
    protected $casts = [
        'id' => 'integer',
    ];
}