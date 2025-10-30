<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\EmployeeContract;
use App\Models\Employee;
use App\Models\EmployeeTimeOff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use App\Models\EmployeeSchedule;
use App\Models\Appointment;

class EmployeeRepository implements EmployeeContract
{
    // ─────────────── GENERIC CRUD ───────────────
    public function list()
    {
        return Employee::with('services')->where('is_deleted', false)->latest('Id')->get();
    }

    public function find($id)
    {
        return Employee::with(['services', 'timeOffs'])->findOrFail($id);
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::create($data);
            if (isset($data['service_ids'])) {
                $employee->services()->sync($data['service_ids']);
            }
            DB::commit();
            return $employee;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to create employee: " . $e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($id);
            $employee->update($data);
            if (isset($data['service_ids'])) {
                $employee->services()->sync($data['service_ids']);
            }
            DB::commit();
            return $employee;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to update employee: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();
        // $employee->update(['is_deleted' => true, 'DateModified' => now()]);
    }

    // ─────────────── ACCOUNT-BASED CRUD ───────────────
    public function listByAccount(int $accountId)
    {
        return Employee::where('AccountId', $accountId)
            ->where('is_deleted', false)
            ->orderByDesc('Id')
            ->with('services')
            ->get();
    }

    public function findByAccount(int $accountId, int $id)
    {
        return Employee::where('AccountId', $accountId)
            ->where('is_deleted', false)
            ->with(['services', 'timeOffs'])
            ->findOrFail($id);
    }

    public function createForAccount(int $accountId, array $data)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $data['AccountId'] = $accountId;
            $data['CreatedById'] = $user?->bkUser?->Id ?? null;
            $data['DateCreated'] = now();
            $data['is_deleted'] = false;

            $employee = Employee::create($data);
            if (isset($data['service_ids'])) {
                $employee->services()->sync($data['service_ids']);
            }

            DB::commit();
            return $employee;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to create employee: " . $e->getMessage());
        }
    }

    public function updateForAccount(int $accountId, int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::where('AccountId', $accountId)
                ->where('is_deleted', false)
                ->findOrFail($id);

            $user = Auth::user();
            $data['ModifiedById'] = $user?->bkUser?->Id ?? null;
            $data['DateModified'] = now();

            $employee->update($data);
            if (isset($data['service_ids'])) {
                $employee->services()->sync($data['service_ids']);
            }

            DB::commit();
            return $employee;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to update employee: " . $e->getMessage());
        }
    }

    public function softDeleteByAccount(int $accountId, int $id)
    {
        $employee = Employee::where('AccountId', $accountId)
            ->where('is_deleted', false)
            ->findOrFail($id);

        $employee->delete();

        // $employee->update([
        //     'is_deleted' => true,
        //     'DateModified' => now(),
        // ]);
    }

    // ─────────────── TIME OFF ───────────────
    public function listTimeOffs($employeeId)
    {
        return EmployeeTimeOff::where('employee_id', $employeeId)
            ->latest('id')
            ->get();
    }

    public function createTimeOff($employeeId, array $data)
    {
        return EmployeeTimeOff::create(array_merge($data, ['employee_id' => $employeeId]));
    }

    // ─────────────── SCHEDULE ───────────────
    public function getSchedule($employeeId, $weekStart)
    {
        $start = Carbon::parse($weekStart)->startOfWeek();
        $days = collect();

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $timeOffs = EmployeeTimeOff::where('employee_id', $employeeId)
                ->whereDate('date', $date)
                ->get(['start_time', 'end_time', 'note']);

            $days->push([
                'date' => $date->toDateString(),
                'items' => $timeOffs->map(fn($t) => [
                    'label' => $t->note ?? 'Time Off',
                    'start' => $t->start_time,
                    'end' => $t->end_time,
                    'type' => 'off',
                ]),
            ]);
        }

        return $days;
    }

    // ─────────────── CALENDAR ───────────────
    public function getCalendar($employeeId, $year, $month)
    {
        $start = Carbon::create($year, $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $timeOffs = EmployeeTimeOff::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->map(fn($t) => [
                'id' => "off-{$t->id}",
                'title' => 'Time Off',
                'date' => $t->date,
                'start_time' => $t->start_time,
                'end_time' => $t->end_time,
                'subtitle' => $t->note,
                'is_repeat' => $t->is_repeat,
                'repeat_until' => $t->repeat_until,
                'type' => 'off',
            ]);

        $appointments = Appointment::where('EmployeeId', $employeeId)
            ->whereBetween('StartDateTime', [$start, $end])
            ->get()
            ->map(fn($a) => [
                'id' => "app-{$a->Id}",
                'title' => optional($a->service)->Name ?? 'Appointment',
                'date' => $a->StartDateTime->toDateString(),
                'start_time' => $a->StartDateTime->format('H:i'),
                'end_time' => $a->EndDateTime->format('H:i'),
                'subtitle' => optional($a->customer)->FirstName ?? '',
                'type' => 'appointment',
            ]);

        // ✅ FIX: wrap both in collect() before merge
        return collect($timeOffs)
            ->merge(collect($appointments))
            ->sortBy('date')
            ->values();
    }



    public function listSchedules($employeeId, $weekStart)
    {
        $start = Carbon::parse($weekStart)->startOfWeek();
        $end = $start->copy()->endOfWeek();

        return EmployeeSchedule::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();
    }

    public function createSchedule($employeeId, array $data)
    {
        return EmployeeSchedule::create([
            'employee_id' => $employeeId,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'note' => $data['note'] ?? null,
        ]);
    }

}
