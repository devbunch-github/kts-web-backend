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

    public function fetchPurchasedGiftCards($clientUserId)
    {
        return $this->repo->getPurchasedGiftCards($clientUserId);
    }

    public function cancelAppointment($clientId, $appointmentId, $reason)
    {
        return $this->repo->cancelAppointment($clientId, $appointmentId, $reason);
    }

    public function rescheduleAppointment($clientId, $appointmentId, $date, $time)
    {
        return $this->repo->rescheduleAppointment($clientId, $appointmentId, $date, $time);
    }

    public function leaveReview($clientId, $appointmentId, $rating, $review)
    {
        return $this->repo->leaveReview($clientId, $appointmentId, $rating, $review);
    }


}