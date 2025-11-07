<?php

namespace App\Services\Business;

use App\Repositories\Contracts\{RotaRepositoryInterface, TimeOffRepositoryInterface};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\EmployeeRota;
use Illuminate\Support\Facades\DB;

class RotaService
{
    public function __construct(
        private RotaRepositoryInterface $rotas,
        private TimeOffRepositoryInterface $offs
    ) {}

    public function createRegularShifts($accountId, $employeeId, $start, $end, $interval, $days, $note, $userId)
    {
        $recId = (string) Str::uuid();
        $period = CarbonPeriod::create($start, $end);
        $map = [
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7,
        ];
        $enabled = collect($days)->where('enabled', true);
        $enabledDays = $enabled->pluck('day')->map(fn($d) => $map[$d])->toArray();

        // 🧹 Delete overlapping shifts for selected days within the same range
        EmployeeRota::where('AccountId', $accountId)
            ->where('employee_id', $employeeId)
            ->whereBetween('shift_date', [$start, $end])
            ->where(function ($q) use ($enabledDays) {
                foreach ($enabledDays as $dayNum) {
                    $sqlDay = ($dayNum % 7) + 1; // ISO to SQL Server weekday
                    $q->orWhereRaw("DATEPART(WEEKDAY, shift_date) = ?", [$sqlDay]);
                }
            })
            ->delete();

        // 🧮 Generate shifts
        $rows = [];
        $week = 1; // start counting from week 1

        foreach ($period as $d) {
            if ($d->isMonday() && $d->notEqualTo(Carbon::parse($start))) {
                $week++; // increment at each Monday after start date
            }

            // Only include weeks matching the selected interval
            if (($week - 1) % $interval !== 0) continue;

            $match = $enabled->first(fn($x) => $map[$x['day']] === $d->dayOfWeekIso);
            if (!$match) continue;

            $rows[] = [
                'AccountId'     => $accountId,
                'employee_id'   => $employeeId,
                'shift_date'    => $d->toDateString(),
                'start_time'    => $match['start_time'],
                'end_time'      => $match['end_time'],
                'source'        => 'regular',
                'recurrence_id' => $recId,
                'note'          => $note,
                'created_by'    => $userId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        if ($rows) {
            $this->rotas->createMany($rows);
        }

        return $recId;
    }



    public function createTimeOff($accountId, $employeeId, $date, $start, $end, $repeat, $until, $note, $userId) {
        $recId = (string) Str::uuid();
        $from = Carbon::parse($date);
        $to   = $repeat && $until ? Carbon::parse($until) : $from;
        $rows=[];
        foreach (CarbonPeriod::create($from,$to) as $d) {
            $rows[] = [
                'AccountId'=>$accountId,'employee_id'=>$employeeId,
                'date'=>$d->toDateString(),'start_time'=>$start,'end_time'=>$end,
                'is_repeat'=>$repeat,'repeat_until'=>$repeat?$to->toDateString():null,
                'note'=>$note,'recurrence_id'=>$recId,
                'created_at'=>now(),'updated_at'=>now(),
            ];
        }
        if ($rows) $this->offs->createMany($rows);
        return $recId;
    }
}
