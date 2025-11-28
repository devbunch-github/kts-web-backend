<?php

namespace App\Repositories\Eloquent;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Account;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;

class AppointmentRepository
{
    public function listByAccount(int $accountId, array $filters = [])
    {
        try {
            $query = Appointment::with(['customer', 'service', 'employee'])
                ->where('AccountId', $accountId)
                // ->whereNull('is_deleted') // optional if you track soft deletes
                ->orderByDesc('Id');

            if (!empty($filters['status'])) {
                $query->where('Status', $filters['status']);
            }

            if (!empty($filters['q'])) {
                $query->whereHas('customer', fn($q) =>
                    $q->where('Name', 'like', '%' . $filters['q'] . '%')
                );
            }

            return $query->get();
        } catch (Exception $e) {
            Log::error('AppointmentRepository@listByAccount: ' . $e->getMessage());
            throw new Exception('Unable to fetch appointments.');
        }
    }

    public function findByAccount(int $accountId, int $id)
    {
        try {
            return Appointment::where('AccountId', $accountId)
                // ->whereNull('is_deleted')
                ->with(['customer', 'service'])
                ->findOrFail($id);
        } catch (Exception $e) {
            Log::error("AppointmentRepository@findByAccount($id): " . $e->getMessage());
            throw new Exception('Appointment not found.');
        }
    }

    public function createForAccount(int $accountId, array $data)
    {
        try {
            $user = Auth::user();
            $createdById = $user?->bkUser?->Id ?? $user->id;

            $data['AccountId'] = $accountId;
            $data['CreatedById'] = $createdById;
            $data['DateCreated'] = now();
            $data['FinalAmount'] = $data['FinalAmount'] ?? $data['Cost'];
            $data['Discount']    = $data['Discount'] ?? 0;
            $data['PromoCode']   = $data['PromoCode'] ?? null;
            $data['GiftCardCode']   = $data['GiftCardCode']   ?? null;
            $data['GiftCardAmount'] = $data['GiftCardAmount'] ?? 0;

            // 🔹 Map Status
            $statusMap = [
                'Unpaid' => 0,
                'Paid' => 1,
                'Cancelled' => 2,
            ];

            if (is_numeric($data['Status'])) {
                $data['Status'] = (int) $data['Status'];
            } else {
                $data['Status'] = $statusMap[$data['Status']] ?? 0;
            }

            // 🔹 Auto-calculate EndDateTime from Service duration
            if (!empty($data['ServiceId']) && !empty($data['StartDateTime'])) {
                $service = Service::find($data['ServiceId']);
                if ($service && $service->DefaultAppointmentDuration) {
                    $start = Carbon::parse($data['StartDateTime']);
                    $duration = (int) $service->DefaultAppointmentDuration;
                    $unit = strtolower($service->DurationUnit ?? 'minutes');

                    switch ($unit) {
                        case 'hour':
                        case 'hours':
                            $end = $start->copy()->addHours($duration);
                            break;
                        case 'day':
                        case 'days':
                            $end = $start->copy()->addDays($duration);
                            break;
                        default:
                            // default is minutes
                            $end = $start->copy()->addMinutes($duration);
                    }

                    $data['EndDateTime'] = $end;
                }
            }

            // 🔹 Create Appointment
            $appointment = Appointment::create($data);

            // 🔹 Auto-create Income only if appointment is paid or has amount
            if (!empty($appointment)) {
                $service  = Service::find($appointment->ServiceId);
                $customer = Customer::find($appointment->CustomerId);
                $account  = Account::find($appointment->AccountId);

                // Try to get active accounting period if available
                $accountPeriod = AccountingPeriod::where('AccountId', $account->Id)->first();

                DB::table('Income')->insert([
                    'AccountId'           => $account->Id,
                    'AccountingPeriodId'  => $accountPeriod?->Id,
                    'AppointmentId'       => $appointment->Id,
                    'Amount' => $data['FinalAmount'] ?? $data['Cost'],
                    'PaymentMethod'       => isset($data['PaymentMethod'])
                                                ? ($data['PaymentMethod'] == 'Cash' ? 0 : 1)
                                                : 0,
                    'PaymentDateTime'     => $appointment->StartDateTime,
                    'Description'         => trim(($customer->Name ?? '') . ' - ' . ($service->Name ?? '')),
                    'DateCreated'         => now(),
                    'CreatedById'         => $createdById,
                ]);
            }

            return $appointment;

        } catch (Exception $e) {
            Log::error('AppointmentRepository@createForAccount: ' . $e->getMessage());
            throw new Exception('Failed to create appointment.');
        }
    }

    public function updateForAccount(int $accountId, int $id, array $data)
    {
        try {
            $appointment = $this->findByAccount($accountId, $id);
            $user = Auth::user();
            $modifiedById = $user?->bkUser?->Id ?? null;

            $data['ModifiedById'] = $modifiedById;
            $data['DateModified'] = now();

            // 🔹 Normalize Status
            $statusMap = [
                'Unpaid' => 0,
                'Paid' => 1,
                'Cancelled' => 2,
            ];

            if (is_numeric($data['Status'])) {
                $data['Status'] = (int) $data['Status'];
            } else {
                $data['Status'] = $statusMap[$data['Status']] ?? 0;
            }

            // 🔹 Recalculate EndDateTime if StartDateTime or ServiceId changed
            if (!empty($data['StartDateTime']) || !empty($data['ServiceId'])) {
                $serviceId = $data['ServiceId'] ?? $appointment->ServiceId;
                $startTime = $data['StartDateTime'] ?? $appointment->StartDateTime;

                $service = Service::find($serviceId);

                if ($service) {
                    $duration = (int) ($service->DefaultAppointmentDuration ?? 0);
                    $unit = strtolower($service->DurationUnit ?? 'minutes');

                    $start = Carbon::parse($startTime);

                    switch ($unit) {
                        case 'hours':
                        case 'hour':
                            $end = $start->copy()->addHours($duration);
                            break;
                        case 'days':
                        case 'day':
                            $end = $start->copy()->addDays($duration);
                            break;
                        default:
                            $end = $start->copy()->addMinutes($duration);
                            break;
                    }

                    $data['EndDateTime'] = $end;
                }
            }

            // 🔹 Perform Appointment Update
            $appointment->update($data);
            $appointment->refresh();

            // 🔹 Sync Income record
            $service  = Service::find($appointment->ServiceId);
            $customer = Customer::find($appointment->CustomerId);
            $account  = Account::find($appointment->AccountId);
            $accountPeriod = AccountingPeriod::where('AccountId', $account->Id)->first();

            // Try to find existing Income
            $existingIncome = DB::table('Income')->where('AppointmentId', $appointment->Id)->first();

            $incomeData = [
                'AccountId'           => $account->Id,
                'AccountingPeriodId'  => $accountPeriod?->Id,
                'AppointmentId'       => $appointment->Id,
                'Amount'              => $data['Cost'] ?? $appointment->Cost,
                'PaymentMethod'       => isset($data['PaymentMethod'])
                                            ? ($data['PaymentMethod'] == 'Cash' ? 0 : 1)
                                            : 0,
                'PaymentDateTime'     => $appointment->StartDateTime,
                'Description'         => trim(($customer->Name ?? '') . ' - ' . ($service->Name ?? '')),
                'DateModified'        => now(),
                'ModifiedById'        => $modifiedById,
            ];

            if ($existingIncome) {
                // 🔹 Update existing income record
                DB::table('Income')->where('Id', $existingIncome->Id)->update($incomeData);
            } else {
                // 🔹 Create new income if not exists
                $incomeData['DateCreated'] = now();
                $incomeData['CreatedById'] = $modifiedById;
                DB::table('Income')->insert($incomeData);
            }

            return $appointment;

        } catch (Exception $e) {
            Log::error("AppointmentRepository@updateForAccount($id): " . $e->getMessage());
            throw new Exception('Failed to update appointment.');
        }
    }



    public function softDeleteByAccount(int $accountId, int $id)
    {
        try {
            $appointment = $this->findByAccount($accountId, $id);
            $user = Auth::user();
            $modifiedById = $user?->bkUser?->Id ?? null;

            $appointment->delete();

            // $appointment->update([
            //     'is_deleted' => true,
            //     'DateModified' => now(),
            //     'ModifiedById' => $modifiedById,
            // ]);

            return true;
        } catch (Exception $e) {
            Log::error("AppointmentRepository@softDeleteByAccount($id): " . $e->getMessage());
            throw new Exception('Failed to delete appointment.');
        }
    }
}
