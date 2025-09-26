<?php

namespace App\Services;

use App\Repositories\Contracts\ContactRepositoryInterface;

class ContactService
{
    public function __construct(private ContactRepositoryInterface $repo) {}

    public function create(array $data)
    {
        return $this->repo->create($data);
    }
}
