<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Income extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Income';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'AccountId',
        'AccountingPeriodId',
        'AppointmentId',
        'CustomerId',
        'CategoryId',
        'ServiceId',
        'Amount',
        'Description',
        'Notes',
        'PaymentMethod',
        'PaymentDateTime',
        'IsRefund',
        'RefundAmount',
        'CreatedBy',
        'DateCreated', // if your table has this
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->CreatedById = $model->CreatedById ?? (auth()->id() ?? 0);
            // Automatically fill DateCreated if exists
            if (Schema::hasColumn($model->getTable(), 'DateCreated')) {
                $model->DateCreated = $model->DateCreated ?? now();
            }

            // Set CreatedBy if available
            if (Schema::hasColumn($model->getTable(), 'CreatedBy')) {
                $model->CreatedBy = $model->CreatedBy ?? (auth()->id() ?? 0);
            }

            if (empty($model->Description)) {
                $model->Description = $model->Notes ?? 'N/A';
            }
        });
    }

    public function setPaymentDateTimeAttribute($value)
    {
        if (!empty($value)) {
            try {
                $this->attributes['PaymentDateTime'] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $this->attributes['PaymentDateTime'] = now();
            }
        }
    }


    // Relationships (optional)
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'CustomerId', 'Id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'CategoryId', 'id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'ServiceId', 'Id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'AccountId', 'Id');
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'AccountingPeriodId', 'Id');
    }
}
