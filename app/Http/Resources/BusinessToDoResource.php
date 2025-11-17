<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessToDoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => (string) $this->id,
            'title'        => $this->title,
            'due_datetime' => optional($this->due_datetime)?->toIso8601String(),
            'is_completed' => (bool) $this->is_completed,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
