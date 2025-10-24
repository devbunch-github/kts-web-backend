<?php

namespace App\Services\Business;

use App\Models\Income;
use App\Models\Expenses;
use App\Repositories\Eloquent\AppointmentRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessDashboardService
{
    public function __construct(private AppointmentRepository $appointments) {}

    /**
     * Uses your schema:
     * - Income: table `Income`, date col: `DateCreated`, amount: `Amount`
     * - Expenses: table `Expenses`, date col: prefer `PaidDateTime` (falls back to `DateCreated`)
     * - Accounts tax rate column assumed `tax_rate` (adjust if different)
     */
    public function getMonthlySummary(?int $accountId): array
    {
        $start = Carbon::now()->startOfMonth()->startOfDay();
        $end   = Carbon::now()->endOfMonth()->endOfDay();

        $income = Income::where('AccountId', $accountId)
            ->whereBetween('DateCreated', [$start, $end])
            ->sum('Amount');

        $expenses = Expenses::where('AccountId', $accountId)
            ->where(function ($q) use ($start, $end) {
                // Prefer PaidDateTime if present, else DateCreated
                $q->whereBetween('PaidDateTime', [$start, $end])
                  ->orWhereBetween('DateCreated', [$start, $end]);
            })
            ->sum('Amount');

        $profit = $income - $expenses;

        $taxRate = (float) (DB::table('accounts')->where('Id', $accountId)->value('tax_rate') ?? 0);
        $taxAndNi = round($profit * ($taxRate / 100), 2);

        return [
            'tax_and_ni'       => $taxAndNi,
            'current_profit'   => $profit,
            'current_income'   => $income,
            'current_expenses' => $expenses,
            'period'           => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
        ];
    }

    /**
     * Appointments via your AppointmentRepository (already present).
     * Appointment model fields: StartDateTime, EndDateTime, Status, Service relation.
     */
    public function getCalendarEvents(?int $accountId, ?string $start, ?string $end): array
    {
        $filters = [];
        if ($start && $end) {
            // your repo expects filters; it already supports date range by created code style
            $filters['start_date'] = $start;
            $filters['end_date'] = $end;
        }

        $rows = $this->appointments->listByAccount($accountId, $filters);

        return collect($rows)->map(function ($a) {
            // soft palette to match your UI
            $bg = match (strtolower($a->Status ?? '')) {
                'confirmed' => '#EBD3D0',
                'pending'   => '#F6E7DA',
                'cancelled' => '#E8E8E8',
                default     => '#EBD3D0',
            };

            $title = $a->title
                ?? ($a->service?->Name
                ?? ($a->customer?->Name ? ($a->customer->Name . ' — Appointment') : 'Appointment'));

            return [
                'id'              => $a->Id,
                'title'           => $title,
                'start'           => $a->StartDateTime,
                'end'             => $a->EndDateTime,
                'backgroundColor' => $bg,
                'borderColor'     => $bg,
            ];
        })->toArray();
    }
}
