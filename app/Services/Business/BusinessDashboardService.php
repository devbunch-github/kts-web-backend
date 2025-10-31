<?php

namespace App\Services\Business;

use App\Models\Income;
use App\Models\Expenses;
use App\Repositories\Eloquent\AppointmentRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Appointment;
use InvalidArgumentException;

class BusinessDashboardService
{
    public function __construct(private AppointmentRepository $appointments) {}

    /**
     * Get current and forecasted accounting overview (Revenue, Expenses, Profit, Tax, NIC)
     */
    public function getAccountingOverview(Request $request, ?int $accountId): array
    {
        $accountingPeriodId = $request->query('accountingPeriodId');
        $taxFreeCategories = [20, 21, 22];
        $response = [];

        $account = Account::find($accountId);
        if (!$account) {
            return ['error' => 'Account not found'];
        }

        // Resolve accounting period
        $period = empty($accountingPeriodId)
            ? AccountingPeriod::where('AccountId', $account->Id)->first()
            : AccountingPeriod::where('Id', $accountingPeriodId)->where('AccountId', $account->Id)->first();

        if (!$period) {
            return ['error' => 'Accounting period not found'];
        }

        // Days since start of accounting period
        $daysLapsed = DB::table('AccountingPeriods')
            ->where('AccountId', $account->Id)
            ->select(DB::raw('DATEDIFF(DAY, StartDateTime, CAST(GETDATE() AS DATE)) AS days_difference'))
            ->value('days_difference') ?? 0;
        $daysLapsed = max(1, (int) $daysLapsed); // avoid divide-by-zero

        // UTC conversion
        $startDateUTC = Carbon::parse($period->StartDateTime)->utc()->subHours(5);
        $endDateUTC   = Carbon::parse($period->EndDateTime)->utc();

        // INCOME
        $income = Income::where('AccountId', $account->Id)
            ->whereBetween('PaymentDateTime', [$startDateUTC, $endDateUTC])
            ->sum('Amount');

        // TIPS
        $tips = Appointment::where('AccountId', $account->Id)
            ->whereBetween('StartDateTime', [$startDateUTC, $endDateUTC])
            ->whereNull('CancellationDate')
            ->sum('Tip');

        $revenue = $income + $tips;

        // EXPENSES (excluding tax-free categories)
        $expenses = Expenses::where('AccountId', $account->Id)
            ->whereBetween('PaidDateTime', [$startDateUTC, $endDateUTC])
            ->whereNotIn('CategoryId', $taxFreeCategories)
            ->sum('Amount');

        $profit = $revenue - $expenses;

        // Forecast logic
        if ($endDateUTC->isPast()) {
            $estimatedIncome   = $revenue;
            $estimatedExpenses = $expenses;
            $forecastProfit    = $profit;
        } else {
            $estimatedIncome   = ($revenue / $daysLapsed) * 365;
            $estimatedExpenses = ($expenses / $daysLapsed) * 365;
            $forecastProfit    = $estimatedIncome - $estimatedExpenses;
        }

        // Build response
        $response['current'] = [
            'revenue'       => $revenue,
            'expenses'      => $expenses,
            'profit'        => $profit,
            'taxLiability'  => $this->getTaxLiability($profit, $period, $account),
            'nic'           => $this->getNIC($profit, $period),
        ];

        $response['forecasted'] = [
            'revenue'       => $estimatedIncome,
            'expenses'      => $estimatedExpenses,
            'profit'        => $forecastProfit,
            'taxLiability'  => $this->getTaxLiability($forecastProfit, $period, $account),
            'nic'           => $this->getNIC($forecastProfit, $period),
        ];

        $response['period'] = [
            'start'        => $startDateUTC->toDateString(),
            'end'          => $endDateUTC->toDateString(),
            'days_lapsed'  => $daysLapsed,
        ];

        return $response;
    }

    /**
     * -----------------------------
     * TAX & NIC COMPUTATION SECTION
     * -----------------------------
     */

    public function getTaxLiability($profit, $period, $account)
    {
        $totalIncome = $profit + ($period->AnnualEmploymentIncome ?? 0);
        $taxFreeAllowance = $this->CalculateFreeTaxAllowance($totalIncome);
        $taxableIncome = $totalIncome - $taxFreeAllowance;

        switch ($account->Country) {
            case 0: // England / Northern Ireland
                $totalTax = $this->CalculateIncomeTaxEnglandAndNorthernIreland($period, $taxableIncome);
                if ($period->AnnualEmploymentIncome != 0) {
                    $employmentTax = $this->CalculateIncomeTaxEnglandAndNorthernIreland(
                        $period,
                        $period->AnnualEmploymentIncome - $taxFreeAllowance
                    );
                    $totalTax -= $employmentTax;
                }
                break;

            case 1: // Scotland
                $totalTax = $this->CalculateIncomeTaxScotland($period, $taxableIncome);
                if ($period->AnnualEmploymentIncome != 0) {
                    $employmentTax = $this->CalculateIncomeTaxScotland(
                        $period,
                        $period->AnnualEmploymentIncome - $taxFreeAllowance
                    );
                    $totalTax -= $employmentTax;
                }
                break;

            case 2: // Wales
                $totalTax = $this->CalculateIncomeTaxWales($period, $taxableIncome);
                if ($period->AnnualEmploymentIncome != 0) {
                    $employmentTax = $this->CalculateIncomeTaxWales(
                        $period,
                        $period->AnnualEmploymentIncome - $taxFreeAllowance
                    );
                    $totalTax -= $employmentTax;
                }
                break;

            default:
                throw new InvalidArgumentException("Unknown country code: {$account->Country}");
        }

        return round($totalTax, 2);
    }

    public function CalculateFreeTaxAllowance($totalIncome)
    {
        $taxFreeAllowance = 12570;
        if ($totalIncome > 100000) {
            $surplus = $totalIncome - 100000;
            $taxFreeAllowance -= ($surplus / 2);
            $taxFreeAllowance = max(0, $taxFreeAllowance);
        }
        return $taxFreeAllowance;
    }

    public function CalculateIncomeTaxEnglandAndNorthernIreland($period, $taxableIncome)
    {
        $taxableIncome = max(0, $taxableIncome);
        $basic = $higher = $additional = 0;

        if ($taxableIncome <= 37700) {
            $basic = $taxableIncome * 0.2;
        } elseif ($taxableIncome <= 125140) {
            $basic = 37700 * 0.2;
            $higher = ($taxableIncome - 37700) * 0.4;
        } else {
            $basic = 37700 * 0.2;
            $higher = (125140 - 37700) * 0.4;
            $additional = ($taxableIncome - 125140) * 0.45;
        }

        return $basic + $higher + $additional;
    }

    public function CalculateIncomeTaxScotland($period, $taxableIncome)
    {
        $taxableIncome = max(0, $taxableIncome);
        $starter = $basic = $intermediate = $higher = $advanced = $top = 0;

        $starterTaxVal = 2305;
        $basicTaxVal = 11684;
        $intermediateTaxVal = 17100;
        $higherTaxVal = 31337;
        $advancedTaxVal = 50139;

        // Tax year 2024-25 brackets
        if (
            Carbon::parse($period->StartDateTime)->isSameDay('2024-04-06') &&
            Carbon::parse($period->EndDateTime)->isSameDay('2025-04-05')
        ) {
            if ($taxableIncome <= 2305) {
                $starter = $taxableIncome * 0.19;
            } elseif ($taxableIncome <= 13991) {
                $starter = 2305 * 0.19;
                $basic = ($taxableIncome - 2305) * 0.2;
            } elseif ($taxableIncome <= 31092) {
                $starter = $starterTaxVal * 0.19;
                $basic = $basicTaxVal * 0.2;
                $intermediate = ($taxableIncome - ($starterTaxVal + $basicTaxVal)) * 0.21;
            } elseif ($taxableIncome <= 62430) {
                $starter = $starterTaxVal * 0.19;
                $basic = $basicTaxVal * 0.2;
                $intermediate = $intermediateTaxVal * 0.21;
                $higher = ($taxableIncome - ($starterTaxVal + $basicTaxVal + $intermediateTaxVal)) * 0.42;
            } elseif ($taxableIncome <= 112570) {
                $starter = $starterTaxVal * 0.19;
                $basic = $basicTaxVal * 0.2;
                $intermediate = $intermediateTaxVal * 0.21;
                $higher = $higherTaxVal * 0.42;
                $advanced = ($taxableIncome - ($starterTaxVal + $basicTaxVal + $intermediateTaxVal + $higherTaxVal)) * 0.45;
            } else {
                $starter = $starterTaxVal * 0.19;
                $basic = $basicTaxVal * 0.2;
                $intermediate = $intermediateTaxVal * 0.21;
                $higher = $higherTaxVal * 0.42;
                $advanced = $advancedTaxVal * 0.45;
                $top = ($taxableIncome - ($starterTaxVal + $basicTaxVal + $intermediateTaxVal + $higherTaxVal + $advancedTaxVal)) * 0.48;
            }
        }

        return $starter + $basic + $intermediate + $higher + $advanced + $top;
    }

    public function CalculateIncomeTaxWales($period, $taxableIncome)
    {
        $taxableIncome = max(0, $taxableIncome);
        $basic = $higher = $additional = 0;

        if ($taxableIncome <= 37700) {
            $basic = $taxableIncome * 0.2;
        } elseif ($taxableIncome <= 125140) {
            $basic = 37700 * 0.2;
            $higher = ($taxableIncome - 37700) * 0.4;
        } else {
            $basic = 37700 * 0.2;
            $higher = (125140 - 37700) * 0.4;
            $additional = ($taxableIncome - 125140) * 0.45;
        }

        return $basic + $higher + $additional;
    }

    public function getNIC($profit, $period)
    {
        $niTaxableIncome = $profit - 12570.00;
        $niBasic = $niHigher = $niClass2 = $nicFixCharge = 0.0;

        if (
            Carbon::parse($period->StartDateTime)->isSameDay('2024-04-06') &&
            Carbon::parse($period->EndDateTime)->isSameDay('2025-04-05')
        ) {
            // Tax year 2024–2025
            if ($niTaxableIncome > 0 && $niTaxableIncome <= 37700) {
                $nicFixCharge = 0;
                $niBasic = $niTaxableIncome * 0.06;
            } elseif ($niTaxableIncome > 37700) {
                $nicFixCharge = 0;
                $niBasic = 37700 * 0.06;
                $niHigher = ($niTaxableIncome - 37700) * 0.02;
            }
        } else {
            // Older tax year
            if ($niTaxableIncome > 0 && $niTaxableIncome <= 37700) {
                $nicFixCharge = 179.40;
                $niBasic = $niTaxableIncome * 0.09;
            } elseif ($niTaxableIncome > 37700) {
                $nicFixCharge = 179.40;
                $niBasic = 37700 * 0.09;
                $niHigher = ($niTaxableIncome - 37700) * 0.02;
            }
        }

        return round($niBasic + $niHigher + $niClass2 + $nicFixCharge, 2);
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
