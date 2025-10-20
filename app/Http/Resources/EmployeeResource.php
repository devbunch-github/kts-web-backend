<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        $baseUrl = config('app.url');
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'phone' => $this->phone,
            'email' => $this->email,
            'image'       => $this->image 
                                ? (str_starts_with($this->image, 'http') 
                                    ? $this->image 
                                    : $baseUrl . '/' . ltrim($this->image, '/'))
                                : null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'services' => $this->whenLoaded('services', fn() => $this->services->pluck('name')),
            'services_full' => $this->whenLoaded('services', $this->services),
            'time_offs' => $this->whenLoaded('timeOffs'),
        ];

    }
}
