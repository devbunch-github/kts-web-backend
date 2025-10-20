<?php

namespace App\Repositories\Eloquent;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

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
            $createdById = $user?->bkUser?->Id ?? null;

            $data['AccountId'] = $accountId;
            $data['CreatedById'] = $createdById;
            $data['DateCreated'] = now();

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


            return Appointment::create($data);
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

            $appointment->update($data);
            return $appointment->refresh();

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
