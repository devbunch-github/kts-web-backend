<?php

namespace App\Repositories\Eloquent;

use App\Models\EmployeeTimeOff;
use App\Repositories\Contracts\TimeOffRepositoryInterface;
use Carbon\Carbon;

class TimeOffRepository implements TimeOffRepositoryInterface
{
    public function listForRange(int $accountId, ?int $employeeId, Carbon $from, Carbon $to): array {
        $q = EmployeeTimeOff::where('AccountId',$accountId)
            ->whereBetween('date',[$from->toDateString(),$to->toDateString()]);
        if ($employeeId) $q->where('employee_id',$employeeId);
        return $q->orderBy('date')->get()->groupBy('employee_id')->toArray();
    }

    public function createMany(array $rows): void {
        EmployeeTimeOff::insert($rows);
    }

    public function queryForAccount(int $accountId)
    {
        return EmployeeTimeOff::where('AccountId', $accountId);
    }


    public function deleteByRecurrence(int $accountId, string $recurrenceId): int {
        return EmployeeTimeOff::where('AccountId',$accountId)
            ->where('recurrence_id',$recurrenceId)->delete();
    }
}
