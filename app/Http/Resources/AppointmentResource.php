<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'customer'       => $this->customer?->name,
            'service'        => $this->service?->name,
            'cost'           => $this->Cost,
            'deposit'        => $this->Deposit,
            'refund_amount'  => $this->RefundAmount,
            'discount'       => $this->Discount,
            'final_amount'   => $this->FinalAmount,
            'tip'            => $this->Tip,
            'status'         => $this->Status,
            'start'          => optional($this->StartDateTime)->format('Y-m-d H:i:s'),
            'end'            => optional($this->EndDateTime)->format('Y-m-d H:i:s'),
            'cancellation'   => optional($this->CancellationDate)->format('Y-m-d'),
        ];
    }
}
