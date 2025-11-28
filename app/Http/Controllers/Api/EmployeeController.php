<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessAdmin\EmployeeRequest;
use App\Repositories\Eloquent\EmployeeRepository;
use App\Http\Resources\EmployeeResource;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\EmployeeSchedule;

class EmployeeController extends Controller
{
    protected $employees;

    public function __construct(EmployeeRepository $employees)
    {
        $this->employees = $employees;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $accId = $this->currentAccountId($request->account_id);
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $data = $this->employees->listByAccount($accId);
            return response()->json(['success' => true, 'data' => EmployeeResource::collection($data)]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message' => 'No account found'], 404);

            $employee = $this->employees->findByAccount($accId, (int)$id);
            return response()->json(['success' => true, 'data' => new EmployeeResource($employee)]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }


    public function store(EmployeeRequest $request): JsonResponse
    {
        try {
            $accId = $this->currentAccountId();
            // \Log::info('Creating new employee', ['data' => $accId]);
            // die();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $employee = $this->employees->createForAccount($accId, $request->validated());
            return response()->json(['success' => true, 'data' => new EmployeeResource($employee)], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(EmployeeRequest $request, $id): JsonResponse
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $employee = $this->employees->updateForAccount($accId, $id, $request->validated());
            return response()->json(['success' => true, 'data' => new EmployeeResource($employee)]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $accId = $this->currentAccountId();
            if (!$accId) return response()->json(['message'=>'No account found'],404);

            $this->employees->softDeleteByAccount($accId, $id);
            return response()->json(['success' => true, 'message' => 'Employee deleted successfully.']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function timeOffs($employeeId)
    {
        $data = $this->employees->listTimeOffs($employeeId);
        return response()->json($data);
    }

    public function storeTimeOff(Request $request, $employeeId)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'is_repeat' => 'boolean',
            'repeat_until' => 'nullable|date|after_or_equal:date',
            'note' => 'nullable|string|max:255',
        ]);

        $off = $this->employees->createTimeOff($employeeId, $validated);
        return response()->json($off, 201);
    }

    public function schedule(Request $request, $employeeId)
    {
        $start = $request->get('week_start', now()->startOfWeek());
        $data = $this->employees->getSchedule($employeeId, $start);
        return response()->json(['days' => $data]);
    }

    public function calendar(Request $request, $employeeId)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $data = $this->employees->getCalendar($employeeId, $year, $month);
        return response()->json($data);
    }

    protected function currentAccountId($accountId = null): ?int
    {
        if($accountId == null) {

            if (Auth::check()) {
                return Auth::user()?->bkUser?->account?->Id;
            }

            $userId = request()->header('X-User-Id') ?? request('user_id');
            if ($userId) {
                $user = User::find($userId);
                return $user?->bkUser?->account?->Id;
            }
        } else {
            return $accountId;
        }

        return null;
    }

    public function storeSchedule(Request $request, $employeeId)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'note' => 'nullable|string|max:255',
        ]);

        $schedule = $this->employees->createSchedule($employeeId, $validated);

        return response()->json(['success' => true, 'data' => $schedule]);
    }

    public function weekSchedule($employeeId, Request $request)
    {
        $weekStart = $request->get('week_start', now()->toDateString());
        $start = Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

        // fetch employee schedules within that week
        $schedules = EmployeeSchedule::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $week = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();

            // find all schedules for this day
            $daySchedules = $schedules->where('date', $date)->values();

            $week[] = [
                'date' => $date,
                'items' => $daySchedules->map(fn($s) => [
                    'type' => 'shift',
                    'label' => $s->note ?: 'Shift',
                    'start' => substr($s->start_time, 0, 5),
                    'end'   => substr($s->end_time, 0, 5),
                ])->values(),
            ];
        }

        return response()->json(['days' => $week]);
    }


}
