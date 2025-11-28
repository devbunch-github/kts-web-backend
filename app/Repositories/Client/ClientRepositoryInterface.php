<?php

namespace App\Repositories\Client;

interface ClientRepositoryInterface
{
    public function getAppointmentsByClient($clientId);
    public function getPurchasedGiftCards($clientUserId);
    public function cancelAppointment($clientId, $appointmentId, $reason);
    public function rescheduleAppointment($clientId, $appointmentId, $date, $time);
    public function leaveReview($clientId, $appointmentId, $rating, $review);

}
