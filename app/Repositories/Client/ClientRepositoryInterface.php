<?php

namespace App\Repositories\Client;

interface ClientRepositoryInterface
{
    public function getAppointmentsByClient($clientId);
}
