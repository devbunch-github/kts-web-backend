<?php

// app/Http/Requests/Business/SiteSettingRequest.php
namespace App\Http\Requests\BusinessAdmin;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hero_text' => ['nullable','string','max:180'],
            'colors.background' => ['nullable','regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'colors.text'       => ['nullable','regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'colors.key'        => ['nullable','regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'colors.dark'       => ['nullable','regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'logo'        => ['nullable','image','mimes:png,jpg,jpeg,webp,svg','max:2048'],
            'cover_image' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:4096'],
        ];
    }
}
