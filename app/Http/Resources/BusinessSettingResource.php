<?php

// app/Http/Resources/Business/BusinessSettingResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BusinessSettingResource extends JsonResource
{
    public function toArray($request)
    {
        $data = $this->data ?? [];

        // Map stored paths to public URLs when present
        if (!empty($data['logo_path'])) {
            $data['logo_url'] = Storage::disk('public')->url($data['logo_path']);
        }
        if (!empty($data['cover_path'])) {
            $data['cover_url'] = Storage::disk('public')->url($data['cover_path']);
        }

        return [
            'type' => $this->type,
            'data' => $data,
        ];
    }
}
