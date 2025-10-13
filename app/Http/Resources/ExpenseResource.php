<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->Id,
            'supplier' => $this->Supplier,
            'amount' => $this->Amount,
            'category_id' => $this->CategoryId,
            'notes' => $this->Notes,
            'receipt_id' => $this->ReciptId,
            'payment_method' => $this->PaymentMethod == 0 ? 'Cash' : 'Bank/Card',
            'paid_date_time' => Carbon::parse($this->PaidDateTime)->toDateTimeString(),
            'recurring' => $this->recurring,
            'next_execution_date' => $this->next_execution_date,
            'recurring_created_at' => $this->recurring_created_at,
            'parentId' => $this->parentId,
            'date_created' => $this->DateCreated,
            'created_by' => $this->CreatedById
        ];
    }
}
