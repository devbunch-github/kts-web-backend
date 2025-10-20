<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class CustomerRepository
{
    public function listByAccount(int $accountId)
    {
        try {
            return Customer::where('AccountId', $accountId)
                ->where('is_deleted', false)
                ->orderByDesc('Id')
                ->get();
        } catch (Exception $e) {
            Log::error('CustomerRepository@listByAccount: '.$e->getMessage());
            throw new Exception('Unable to fetch customers.');
        }
    }

    public function findByAccount(int $accountId, int $id)
    {
        try {
            return Customer::where('AccountId', $accountId)
                ->where('is_deleted', false)
                ->findOrFail($id);
        } catch (Exception $e) {
            Log::error("CustomerRepository@findByAccount($id): ".$e->getMessage());
            throw new Exception('Customer not found.');
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
            $data['is_deleted'] = false;

            return Customer::create($data);
        } catch (Exception $e) {
            Log::error('CustomerRepository@createForAccount: '.$e->getMessage());
            throw new Exception('Failed to create customer.');
        }
    }

    public function updateForAccount(int $accountId, int $id, array $data)
    {
        try {
            $row = $this->findByAccount($accountId, $id);
            $user = Auth::user();
            $modifiedById = $user?->bkUser?->Id ?? null;

            $data['ModifiedById'] = $modifiedById;
            $data['DateModified'] = now();

            $row->update($data);
            return $row->refresh();
        } catch (Exception $e) {
            Log::error("CustomerRepository@updateForAccount($id): ".$e->getMessage());
            throw new Exception('Failed to update customer.');
        }
    }

    public function softDeleteByAccount(int $accountId, int $id): bool
    {
        try {
            $row = $this->findByAccount($accountId, $id);
            $user = Auth::user();
            $modifiedById = $user?->bkUser?->Id ?? null;

            $row->update([
                'is_deleted' => true,
                'DateModified' => now(),
                'ModifiedById' => $modifiedById,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error("CustomerRepository@softDeleteByAccount($id): ".$e->getMessage());
            throw new Exception('Failed to delete customer.');
        }
    }

    public function findOrCreate(array $data, int $accountId, int $createdById = 0)
    {
        $existing = Customer::where('AccountId', $accountId)
            ->where('Name', $data['Name'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return Customer::create([
            'Name'         => $data['Name'],
            'Email'        => $data['Email'] ?? null,
            'MobileNumber' => $data['MobileNumber'] ?? null,
            'AccountId'    => $accountId,
            'CreatedById'  => $createdById,
        ]);
    }
}
