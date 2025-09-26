<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\ContactRepositoryInterface ;
use App\Models\ContactMessage;

class ContactRepository implements ContactRepositoryInterface  {
    public function create(array $data) {
        return ContactMessage::create($data);
    }
}
