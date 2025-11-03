<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountEmailTemplateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'       => $this->id,
            'title'    => $this->emailTemplate->title ?? '',
            'key'      => $this->emailTemplate->key ?? '',
            'subject'  => $this->subject ?? $this->emailTemplate->subject,
            'status'   => (bool) $this->status,
            'body'     => $this->body ?? $this->emailTemplate->body,
            'logo_url' => $this->logo_url,
        ];
    }
}
