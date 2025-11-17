<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessFormResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'AccountId'  => $this->AccountId,
            'title'      => $this->title,
            'frequency'  => $this->frequency,
            'is_active'  => (bool)$this->is_active,
            'services'   => $this->whenLoaded('services', fn() => $this->services->pluck('id')),
            'questions'  => BusinessFormQuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
