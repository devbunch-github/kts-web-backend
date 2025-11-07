<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessFormQuestionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'label'      => $this->label,
            'required'   => (bool)$this->required,
            'sort_order' => $this->sort_order,
            'options'    => $this->options,
        ];
    }
}
