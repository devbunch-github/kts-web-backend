<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ClientService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * GET /api/client/appointments
     * Fetch logged-in customer's appointments.
     */
    public function appointments(Request $request)
    {
        $clientId = $request->user()->id;

        $appointments = $this->clientService->fetchAppointments(auth()->id());
        // $appointments = $this->clientService->fetchAppointments(1);

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }
}
