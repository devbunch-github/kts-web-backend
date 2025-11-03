<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PromoCodeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'code'           => $this->code,
            'service'        => $this->service?->Name ?? $this->service?->name,
            'service_id'     => $this->service_id,
            'discount_type'  => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_label' => $this->discount_type === 'percent'
                                 ? "{$this->discount_value}%"
                                 : "£{$this->discount_value}",
            'start_date' => optional($this->start_date)->format('Y-m-d'),
            'end_date' => optional($this->end_date)->format('Y-m-d'),
            'notes'          => $this->notes,
            'status'         => $this->status ? 'Active' : 'Inactive',
            'raw_status'     => (int) $this->status,
        ];
    }
}
