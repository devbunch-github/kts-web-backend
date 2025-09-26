<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BeauticianResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'location'  => $this->location,
            'category'  => $this->category,
            'rating'    => $this->rating,
            'reviews'   => $this->reviews_count,
        ];
    }
}
