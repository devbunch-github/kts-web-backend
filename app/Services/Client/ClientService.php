<?php

namespace App\Services\Client;

use App\Repositories\Client\ClientRepositoryInterface;

class ClientService
{
    protected $repo;

    public function __construct(ClientRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function fetchAppointments($clientId)
    {
        return $this->repo->getAppointmentsByClient($clientId);
    }
}