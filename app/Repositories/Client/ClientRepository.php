<?php

namespace App\Repositories\Client;

use App\Models\Appointment;

class ClientRepository implements ClientRepositoryInterface
{
    public function getAppointmentsByClient($clientId)
    {
        return Appointment::with('service:Id,Name') // adjust column name if needed
            ->where('CustomerId', $clientId)
            ->orderBy('StartDateTime', 'asc')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->Id,
                    'service_name' => $a->service->Name ?? 'Service',
                    'appointment_date' => $a->StartDateTime->format('Y-m-d'),
                    'appointment_time' => $a->StartDateTime->format('H:i'),
                ];
            });
    }
}
