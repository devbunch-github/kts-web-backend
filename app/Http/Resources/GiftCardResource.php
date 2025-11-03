<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftCardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'code'            => $this->code,
            'service_id'      => $this->service_id,
            'service'         => $this->service?->Name ?? 'All services',
            'discount_amount' => $this->discount_amount,
            'discount_type'   => $this->discount_type,
            'start_date'      => optional($this->start_date)->format('Y-m-d'),
            'end_date'        => optional($this->end_date)->format('Y-m-d'),
            'status'          => $this->is_active ? 'Active' : 'Inactive',
            'is_active'       => (bool) $this->is_active,
            'notes'           => $this->notes,
            'image_url'       => $this->image_path ? asset('storage/'.$this->image_path) : null,
        ];
    }
}
