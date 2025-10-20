<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Expenses extends Model
{
    use HasFactory;

    protected $table = 'Expenses';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'AccountId',
        'Supplier',
        'AccountingPeriodId',
        'Amount',
        'CategoryId',
        'Notes',
        'ReciptId',
        'PaymentMethod',
        'PaidDateTime',
        'DateCreated',
        'DateModified',
        'CreatedById',
        'ModifiedById',
        'recurring',
        'recurring_created_at',
        'next_execution_date',
        'parentId',
    ];

    protected $casts = [
        'PaidDateTime' => 'datetime',
        'DateCreated' => 'datetime',
        'DateModified' => 'datetime',
        'recurring_created_at' => 'datetime',
        'next_execution_date' => 'datetime',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'CategoryId', 'Id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'AccountId', 'Id');
    }

    public function childExpenses()
    {
        return $this->hasMany(self::class, 'parentId', 'Id');
    }

    public function parentExpense()
    {
        return $this->belongsTo(self::class, 'parentId', 'Id');
    }

    // Accessors
    public function getPaymentMethodLabelAttribute()
    {
        return $this->PaymentMethod == 0 ? 'Cash' : 'Bank/Card';
    }

    public function getPaidDateFormattedAttribute()
    {
        return $this->PaidDateTime ? Carbon::parse($this->PaidDateTime)->format('Y-m-d') : null;
    }
}
