<?php

namespace App\Repositories\Eloquent;

use App\Models\EmployeeRota;
use App\Repositories\Contracts\RotaRepositoryInterface;
use Carbon\Carbon;

class RotaRepository implements RotaRepositoryInterface
{
    public function listForRange(int $accountId, ?int $employeeId, Carbon $from, Carbon $to): array {
        $q = EmployeeRota::where('AccountId',$accountId)
            ->whereBetween('shift_date',[$from->toDateString(),$to->toDateString()]);
        if ($employeeId) $q->where('employee_id',$employeeId);
        return $q->orderBy('shift_date')->get()->groupBy('employee_id')->toArray();
    }

    public function createMany(array $rows): void {
        EmployeeRota::insert($rows);
    }

    public function deleteByRecurrence(int $accountId, string $recurrenceId): int {
        return EmployeeRota::where('AccountId',$accountId)
            ->where('recurrence_id',$recurrenceId)->delete();
    }
}
