<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;

class CustomerRepository
{
    public function find($id)
    {
        return Customer::find($id);
    }

    public function create(array $data)
    {
        return Customer::create($data);
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
