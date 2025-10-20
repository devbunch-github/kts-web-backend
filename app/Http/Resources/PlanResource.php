<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource {
  public function toArray($req){
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'price'=>number_format($this->price_minor/100, 2),
            'price_minor'=>$this->price_minor,
            'currency'=>$this->currency,
            'duration' => $this->duration,
            'features'=>$this->features ?? [],
        ];
    }
}