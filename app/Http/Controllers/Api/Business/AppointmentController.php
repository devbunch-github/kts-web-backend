<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\AppointmentRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Exception;

class AppointmentController extends Controller
{
    protected AppointmentRepository $appointments;

    public function __construct(AppointmentRepository $appointments)
    {
        $this->appointments = $appointments;
    }

    /**
     * Get current AccountId based on Auth user or provided header
     */
    protected function currentAccountId(): ?int
    {
        if (Auth::check()) {
            return Auth::user()?->bkUser?->account?->Id;
        }

        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
            $user = User::find($userId);
            return $user?->bkUser?->account?->Id;
        }

        return null;
    }

    public function index(Request $request)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $filters = [
                'status' => $request->status,
                'q' => $request->q,
            ];

            $appointments = $this->appointments->listByAccount($accId, $filters);
            return response()->json(['success' => true, 'data' => $appointments]);
        } catch (Exception $e) {
            Log::error('AppointmentController@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Unable to load appointments'], 500);
        }
    }

    public function show($id)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $appointment = $this->appointments->findByAccount($accId, (int)$id);
            return response()->json(['success' => true, 'data' => $appointment]);
        } catch (Exception $e) {
            Log::error("AppointmentController@show: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Appointment not found'], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'CustomerId'    => 'required|integer|exists:Customers,Id',
                'ServiceId'     => 'required|integer|exists:Services,Id',
                'StartDateTime' => 'required|date',
                'EndDateTime'   => 'nullable|date|after:StartDateTime',
                'Cost'          => 'required|numeric|min:0',
                'Deposit'       => 'nullable|numeric|min:0',
                'Tip'           => 'nullable|numeric|min:0',
                'RefundAmount'  => 'nullable|numeric|min:0',
                'Status'        => 'required',
                'EmployeeId'    => 'nullable|integer|exists:Employees,Id',
            ]);

            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $appointment = $this->appointments->createForAccount($accId, $validated);
            return response()->json(['success' => true, 'data' => $appointment], 201);
        } catch (ValidationException $ex) {
            return response()->json(['success' => false, 'errors' => $ex->errors()], 422);
        } catch (Exception $e) {
            Log::error('AppointmentController@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create appointment.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'CustomerId'    => 'required|integer|exists:Customers,Id',
                'ServiceId'     => 'required|integer|exists:Services,Id',
                'StartDateTime' => 'required|date',
                'EndDateTime'   => 'nullable|date',
                'Cost'          => 'required|numeric|min:0',
                'Deposit'       => 'nullable|numeric|min:0',
                'Tip'           => 'nullable|numeric|min:0',
                'RefundAmount'  => 'nullable|numeric|min:0',
                'Status'        => 'required',
                'EmployeeId'    => 'nullable|integer|exists:Employees,Id',
            ]);

            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $appointment = $this->appointments->updateForAccount($accId, (int)$id, $validated);
            return response()->json(['success' => true, 'data' => $appointment]);
        } catch (ValidationException $ex) {
            return response()->json(['success' => false, 'errors' => $ex->errors()], 422);
        } catch (Exception $e) {
            Log::error("AppointmentController@update: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update appointment.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $this->appointments->softDeleteByAccount($accId, (int)$id);
            return response()->json(['success' => true, 'message' => 'Appointment deleted successfully.']);
        } catch (Exception $e) {
            Log::error('AppointmentController@destroy: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete appointment.'], 500);
        }
    }
}
