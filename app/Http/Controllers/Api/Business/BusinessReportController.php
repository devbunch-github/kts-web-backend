<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Models\Expenses;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Services\Business\BusinessDashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Customer;

class BusinessReportController extends Controller
{
    public function __construct(private BusinessDashboardService $service) {}

    public function reportSummary(Request $request)
    {
        $accId = $this->accountId();
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->endOfMonth()->toDateString());

        return response()->json([
            'success' => true,
            'data' => $this->service->getBusinessReports($accId, $from, $to),
        ]);
    }

    public function serviceReport(Request $request)
    {
        $accId = $this->accountId();
        $from = $request->query('from');
        $to = $request->query('to');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->subMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $services = Service::where('AccountId', $accId)
            ->where('IsDeleted', 0)
            ->get()
            ->map(function ($service) use ($accId, $fromDate, $toDate) {
                $incomes = Income::where('AccountId', $accId)
                    ->where('ServiceId', $service->Id)
                    ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
                    ->get();

                $bookedCount = $incomes->count();
                $totalIncome = $incomes->sum('Amount');
                $amount = $service->TotalPrice ?? 0;

                // derive readable duration
                $duration = $service->DefaultAppointmentDuration;
                $unit = strtolower($service->DurationUnit ?? 'mins');
                $durationLabel = match ($unit) {
                    'hour', 'hours', 'hrs' => "{$duration} hrs",
                    'min', 'mins', 'minutes' => "{$duration} mins",
                    default => "{$duration}",
                };

                $minutes = str_contains($unit, 'hour') ? $duration * 60 : $duration;
                $profitPerMin = $minutes > 0 ? round($amount / $minutes, 2) : 0;

                return [
                    'service' => $service->Name,
                    'amount' => $amount,
                    'duration' => $durationLabel,
                    'booked_services' => $bookedCount,
                    'total_income' => $totalIncome,
                    'profit_per_min' => $profitPerMin,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function clientReport(Request $request)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $from = $request->query('from');
        $to = $request->query('to');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $report = Income::select(
                'CustomerId',
                DB::raw('SUM(CAST(Amount AS DECIMAL(18,2))) AS total_spent'),
                DB::raw('COUNT(*) AS visits'),
                DB::raw('MAX(PaymentDateTime) AS last_visit')
            )
            ->where('AccountId', $accId)
            ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
            ->whereNotNull('CustomerId')
            ->groupBy('CustomerId')
            ->with(['customer' => function ($q) {
                $q->select('Id', 'Name');
            }])
            ->get()
            ->map(function ($row) {
                return [
                    'customer_name' => $row->customer?->Name ?? 'N/A',
                    'total_spent' => (float) $row->total_spent,
                    'visits' => (int) $row->visits,
                    'last_visit' => $row->last_visit
                        ? Carbon::parse($row->last_visit)->format('d/m/Y')
                        : '-',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function appointmentCompletionReport(Request $request)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $from = $request->query('from');
        $to = $request->query('to');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $appointments = Appointment::select(
                DB::raw("CONVERT(date, StartDateTime) as date_only"),
                DB::raw("COUNT(*) as booked"),
                DB::raw("SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as canceled")
            )
            ->where('AccountId', $accId)
            ->whereBetween('StartDateTime', [$fromDate, $toDate])
            ->groupBy(DB::raw("CONVERT(date, StartDateTime)"))
            ->orderBy(DB::raw("CONVERT(date, StartDateTime)"), 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => Carbon::parse($row->date_only)->format('d/m/Y'),
                    'booked' => (int) $row->booked,
                    'completed' => (int) $row->completed,
                    'canceled' => (int) $row->canceled,
                ];
            });


        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }

    public function profitLossReport(Request $request)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $from = $request->query('from');
        $to = $request->query('to');

        $fromDate = $from ? Carbon::parse($from)->startOfMonth() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfMonth() : Carbon::now()->endOfYear();

        // --- Income grouped by month ---
        $incomeData = Income::select(
                DB::raw("FORMAT(PaymentDateTime, 'yyyy-MM') AS ym"),
                DB::raw("SUM(CAST(Amount AS DECIMAL(18,2))) AS total_income")
            )
            ->where('AccountId', $accId)
            ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
            ->groupBy(DB::raw("FORMAT(PaymentDateTime, 'yyyy-MM')"))
            ->pluck('total_income', 'ym');

        // --- Expenses grouped by month ---
        $expenseData = Expenses::select(
                DB::raw("FORMAT(PaidDateTime, 'yyyy-MM') AS ym"),
                DB::raw("SUM(CAST(Amount AS DECIMAL(18,2))) AS total_expense")
            )
            ->where('AccountId', $accId)
            ->whereBetween('PaidDateTime', [$fromDate, $toDate])
            ->groupBy(DB::raw("FORMAT(PaidDateTime, 'yyyy-MM')"))
            ->pluck('total_expense', 'ym');

        // --- Merge into one monthly report ---
        $months = collect($incomeData->keys())
            ->merge($expenseData->keys())
            ->unique()
            ->sort()
            ->values();

        $report = $months->map(function ($ym) use ($incomeData, $expenseData) {
            $income = (float) ($incomeData[$ym] ?? 0);
            $expense = (float) ($expenseData[$ym] ?? 0);
            $profit = $income - $expense;

            $carbon = Carbon::createFromFormat('Y-m', $ym);
            return [
                'month' => $carbon->format('F Y'),
                'income' => $income,
                'expenses' => $expense,
                'profit' => $profit,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function cancellationReport(Request $request)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $from = $request->query('from');
        $to = $request->query('to');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $data = Appointment::with(['customer', 'service', 'employee'])
            ->where('AccountId', $accId)
            ->whereBetween('StartDateTime', [$fromDate, $toDate])
            ->where('Status', 2) // ✅ only numeric
            ->orderBy('StartDateTime', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'customer' => $a->customer?->Name ?? '-',
                    'service'  => $a->service?->Name ?? '-',
                    'employee' => $a->employee?->Name ?? '-',
                    'reason'   => $a->CancellationReason ?? '—',
                ];
            });


        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function incomeSaleReport(Request $request)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $from = $request->query('from');
        $to = $request->query('to');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        $incomes = Income::with(['customer', 'category', 'service'])
            ->where('AccountId', $accId)
            ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
            ->orderBy('PaymentDateTime', 'desc')
            ->get()
            ->map(function ($i) {
                // Handle refund (if IsRefund or RefundAmount is true)
                $isRefund = $i->IsRefund || ($i->RefundAmount > 0);

                // Compute net amount
                $amount = $isRefund
                    ? -abs($i->RefundAmount ?: $i->Amount)
                    : ($i->Amount ?: 0);

                $rawDate = $i->PaymentDateTime ?? $i->DateCreated;

                $date = $rawDate
                    ? Carbon::parse(explode('.', $rawDate)[0])->format('d/m/Y')
                    : '-';

                return [
                    'date' => $date,
                    'customer' => $i->customer?->Name ?? '-',
                    'category' => $i->category?->Name ?? '-',
                    'service' => $i->service?->Name ?? '---',
                    'amount' => round($amount, 2),
                    'is_refund' => $isRefund,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $incomes,
        ]);
    }

    public function clientRetentionRate(Request $request)
    {
        $accId = $this->accountId();

        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to   = $request->to   ? Carbon::parse($request->to)->endOfDay()   : now()->endOfMonth();

        // Fetch clients grouped by month of their first visit
        $clientsByMonth = Appointment::where('AccountId', $accId)
            ->whereBetween('StartDateTime', [$from, $to])
            ->whereNotNull('CustomerId')
            ->selectRaw("YEAR(StartDateTime) as year, MONTH(StartDateTime) as month, CustomerId")
            ->get()
            ->groupBy(function ($a) {
                return Carbon::create($a->year, $a->month)->format('Y-m');
            });

        $result = [];

        foreach ($clientsByMonth as $monthKey => $records) {
            [$year, $month] = explode('-', $monthKey);
            $monthName = Carbon::create($year, $month)->format('F Y');

            $uniqueCustomers = $records->pluck('CustomerId')->unique();

            $newClients = 0;
            $returning = 0;

            foreach ($uniqueCustomers as $customerId) {
                $firstAppt = Appointment::where('AccountId', $accId)
                    ->where('CustomerId', $customerId)
                    ->orderBy('StartDateTime', 'asc')
                    ->value('StartDateTime');

                if ($firstAppt) {
                    $firstMonth = Carbon::parse($firstAppt)->format('Y-m');
                    if ($firstMonth === $monthKey) {
                        $newClients++;
                    } else {
                        $returning++;
                    }
                }
            }

            $retentionRate = $newClients + $returning > 0
                ? round(($returning / ($newClients + $returning)) * 100, 2)
                : 0;

            $result[] = [
                'month' => $monthName,
                'new_clients' => $newClients,
                'returning_clients' => $returning,
                'retention_rate' => $retentionRate,
            ];
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

     /* ================================
       Export Report (PDF Generator)
    ================================= */
    public function exportReport(Request $request)
    {
        $type = $request->get('type');
        $from = Carbon::parse($request->get('from'))->startOfDay();
        $to   = Carbon::parse($request->get('to'))->endOfDay();

        switch ($type) {
            case 'service':
                $data = $this->getServiceReportData($from, $to);
                break;
            case 'client':
                $data = $this->getClientReportData($from, $to);
                break;
            case 'appointment':
                $data = $this->getAppointmentCompletionData($from, $to);
                break;
            case 'profitloss':
                $data = $this->getProfitLossData($from, $to);
                break;
            case 'cancellation':
                $data = $this->getCancellationData($from, $to);
                break;
            case 'income':
                $data = $this->getIncomeSalesData($from, $to);
                break;
            case 'retention':
                $data = $this->getRetentionData($from, $to);
                break;
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }

        $pdf = PDF::loadView('reports.export', [
            'type' => ucfirst($type) . ' Report',
            'from' => $from->format('d M Y'),
            'to'   => $to->format('d M Y'),
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Report_' . ucfirst($type) . '_' . now()->format('Ymd_His') . '.pdf');
    }

    /* ================================
       INDIVIDUAL REPORT DATA METHODS
    ================================= */

    protected function getServiceReportData($from, $to)
    {
        $accId = $this->accountId();

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->subMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $services = Service::where('AccountId', $accId)
            ->where('IsDeleted', 0)
            ->get()
            ->map(function ($service) use ($accId, $fromDate, $toDate) {
                $incomes = Income::where('AccountId', $accId)
                    ->where('ServiceId', $service->Id)
                    ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
                    ->get();

                $bookedCount = $incomes->count();
                $totalIncome = $incomes->sum('Amount');
                $amount = $service->TotalPrice ?? 0;

                // derive readable duration
                $duration = $service->DefaultAppointmentDuration;
                $unit = strtolower($service->DurationUnit ?? 'mins');
                $durationLabel = match ($unit) {
                    'hour', 'hours', 'hrs' => "{$duration} hrs",
                    'min', 'mins', 'minutes' => "{$duration} mins",
                    default => "{$duration}",
                };

                $minutes = str_contains($unit, 'hour') ? $duration * 60 : $duration;
                $profitPerMin = $minutes > 0 ? round($amount / $minutes, 2) : 0;

                return [
                    'service' => $service->Name,
                    'amount' => $amount,
                    'duration' => $durationLabel,
                    'booked_services' => $bookedCount,
                    'total_income' => $totalIncome,
                    'profit_per_min' => $profitPerMin,
                ];
            })
            ->values()
            ->toArray();

        return $services;
    }


    protected function getClientReportData($from, $to)
    {
        $accId = $this->accountId();

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        return Income::select(
                'CustomerId',
                DB::raw('SUM(CAST(Amount AS DECIMAL(18,2))) AS total_spent'),
                DB::raw('COUNT(*) AS visits'),
                DB::raw('MAX(PaymentDateTime) AS last_visit')
            )
            ->where('AccountId', $accId)
            ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
            ->whereNotNull('CustomerId')
            ->groupBy('CustomerId')
            ->with(['customer' => function ($q) {
                $q->select('Id', 'Name');
            }])
            ->get()
            ->map(function ($row) {
                return [
                    'customer_name' => $row->customer?->Name ?? 'N/A',
                    'total_spent' => (float) $row->total_spent,
                    'visits' => (int) $row->visits,
                    'last_visit' => $row->last_visit
                        ? Carbon::parse($row->last_visit)->format('d/m/Y')
                        : '-',
                ];
            })
            ->toArray();
    }

    protected function getAppointmentCompletionData($from, $to)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        return Appointment::select(
                DB::raw("CONVERT(date, StartDateTime) as date_only"),
                DB::raw("COUNT(*) as booked"),
                DB::raw("SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as canceled")
            )
            ->where('AccountId', $accId)
            ->whereBetween('StartDateTime', [$fromDate, $toDate])
            ->groupBy(DB::raw("CONVERT(date, StartDateTime)"))
            ->orderBy(DB::raw("CONVERT(date, StartDateTime)"), 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => Carbon::parse($row->date_only)->format('d/m/Y'),
                    'booked' => (int) $row->booked,
                    'completed' => (int) $row->completed,
                    'canceled' => (int) $row->canceled,
                ];
            })
            ->toArray();
    }

    protected function getProfitLossData($from, $to)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $fromDate = $from ? Carbon::parse($from)->startOfMonth() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfMonth() : Carbon::now()->endOfYear();

        // --- Income grouped by month ---
        $incomeData = Income::select(
                DB::raw("FORMAT(PaymentDateTime, 'yyyy-MM') AS ym"),
                DB::raw("SUM(CAST(Amount AS DECIMAL(18,2))) AS total_income")
            )
            ->where('AccountId', $accId)
            ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
            ->groupBy(DB::raw("FORMAT(PaymentDateTime, 'yyyy-MM')"))
            ->pluck('total_income', 'ym');

        // --- Expenses grouped by month ---
        $expenseData = Expenses::select(
                DB::raw("FORMAT(PaidDateTime, 'yyyy-MM') AS ym"),
                DB::raw("SUM(CAST(Amount AS DECIMAL(18,2))) AS total_expense")
            )
            ->where('AccountId', $accId)
            ->whereBetween('PaidDateTime', [$fromDate, $toDate])
            ->groupBy(DB::raw("FORMAT(PaidDateTime, 'yyyy-MM')"))
            ->pluck('total_expense', 'ym');

        // --- Merge into one monthly report ---
        $months = collect($incomeData->keys())
            ->merge($expenseData->keys())
            ->unique()
            ->sort()
            ->values();

        $report = $months->map(function ($ym) use ($incomeData, $expenseData) {
            $income = (float) ($incomeData[$ym] ?? 0);
            $expense = (float) ($expenseData[$ym] ?? 0);
            $profit = $income - $expense;

            $carbon = Carbon::createFromFormat('Y-m', $ym);
            return [
                'month' => $carbon->format('F Y'),
                'income' => $income,
                'expenses' => $expense,
                'profit' => $profit,
            ];
        })
        ->toArray();

        return $report;
    }

    protected function getCancellationData($from, $to)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        return Appointment::with(['customer', 'service', 'employee'])
            ->where('AccountId', $accId)
            ->whereBetween('StartDateTime', [$fromDate, $toDate])
            ->where('Status', 2) // ✅ only numeric
            ->orderBy('StartDateTime', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'customer' => $a->customer?->Name ?? '-',
                    'service'  => $a->service?->Name ?? '-',
                    'employee' => $a->employee?->Name ?? '-',
                    'reason'   => $a->CancellationReason ?? '—',
                ];
            })
            ->toArray();
    }

    protected function getIncomeSalesData($from, $to)
    {
        $accId = $this->accountId();
        if (!$accId) {
            return response()->json(['error' => 'AccountId required'], 400);
        }

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();

        return Income::with(['customer', 'category', 'service'])
            ->where('AccountId', $accId)
            ->whereBetween('PaymentDateTime', [$fromDate, $toDate])
            ->orderBy('PaymentDateTime', 'desc')
            ->get()
            ->map(function ($i) {
                // Handle refund (if IsRefund or RefundAmount is true)
                $isRefund = $i->IsRefund || ($i->RefundAmount > 0);

                // Compute net amount
                $amount = $isRefund
                    ? -abs($i->RefundAmount ?: $i->Amount)
                    : ($i->Amount ?: 0);

                $rawDate = $i->PaymentDateTime ?? $i->DateCreated;

                $date = $rawDate
                    ? Carbon::parse(explode('.', $rawDate)[0])->format('d/m/Y')
                    : '-';

                return [
                    'date' => $date,
                    'customer' => $i->customer?->Name ?? '-',
                    'category' => $i->category?->Name ?? '-',
                    'service' => $i->service?->Name ?? '---',
                    'amount' => round($amount, 2),
                    'is_refund' => $isRefund,
                ];
            })
            ->toArray();
    }

    protected function getRetentionData($from, $to)
    {
        $accId = $this->accountId();

        // Fetch clients grouped by month of their first visit
        $clientsByMonth = Appointment::where('AccountId', $accId)
            ->whereBetween('StartDateTime', [$from, $to])
            ->whereNotNull('CustomerId')
            ->selectRaw("YEAR(StartDateTime) as year, MONTH(StartDateTime) as month, CustomerId")
            ->get()
            ->groupBy(function ($a) {
                return Carbon::create($a->year, $a->month)->format('Y-m');
            });

        $result = [];

        foreach ($clientsByMonth as $monthKey => $records) {
            [$year, $month] = explode('-', $monthKey);
            $monthName = Carbon::create($year, $month)->format('F Y');

            $uniqueCustomers = $records->pluck('CustomerId')->unique();

            $newClients = 0;
            $returning = 0;

            foreach ($uniqueCustomers as $customerId) {
                $firstAppt = Appointment::where('AccountId', $accId)
                    ->where('CustomerId', $customerId)
                    ->orderBy('StartDateTime', 'asc')
                    ->value('StartDateTime');

                if ($firstAppt) {
                    $firstMonth = Carbon::parse($firstAppt)->format('Y-m');
                    if ($firstMonth === $monthKey) {
                        $newClients++;
                    } else {
                        $returning++;
                    }
                }
            }

            $retentionRate = $newClients + $returning > 0
                ? round(($returning / ($newClients + $returning)) * 100, 2)
                : 0;

            $result[] = [
                'month' => $monthName,
                'new_clients' => $newClients,
                'returning_clients' => $returning,
                'retention_rate' => $retentionRate,
            ];
        }

        return $result;
    }

    private function accountId(): ?int
    {
        if (auth()->check()) {
            return auth()->user()?->bkUser?->account?->Id;
        }

        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            return $user?->bkUser?->account?->Id;
        }

        return null;
    }
}
