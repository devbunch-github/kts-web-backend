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

    public function purchasedGiftCards(Request $request)
    {
        $cards = $this->clientService->fetchPurchasedGiftCards(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $cards
        ]);
    }

    public function profile(Request $request)
    {
        $user = auth()->user();
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'confirm_password' => 'nullable|same:password',
        ]);

        // Update Email
        $user->email = $data['email'];

        // Update Password
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'data' => $user
        ]);
    }


     public function cancelAppointment(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $userId = $request->user()->id;

        $ok = $this->clientService->cancelAppointment($userId, $id, $request->reason);

        if (! $ok) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to cancel this appointment.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled successfully.',
        ]);
    }

    // ✅ RESCHEDULE APPOINTMENT (manual date + time)
    public function rescheduleAppointment(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|string', // e.g. "14:30"
        ]);

        $userId = $request->user()->id;

        $ok = $this->clientService->rescheduleAppointment(
            $userId,
            $id,
            $request->date,
            $request->time
        );

        if (! $ok) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reschedule this appointment.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Appointment rescheduled successfully.',
        ]);
    }

    // ✅ LEAVE REVIEW (simple, for now)
    public function leaveReview(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $userId = $request->user()->id;

        $this->clientService->leaveReview(
            $userId,
            $id,
            $request->rating,
            $request->review
        );

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
        ]);
    }



}
