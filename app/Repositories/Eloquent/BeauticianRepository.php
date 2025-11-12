<?php

namespace App\Repositories\Eloquent;

use App\Models\Beautician;
use App\Repositories\Contracts\BeauticianRepositoryInterface;

class BeauticianRepository implements BeauticianRepositoryInterface
{
    public function allWithFilters(array $filters)
    {
        $query = Beautician::query();

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['service'])) {
            $query->whereJsonContains('services', $filters['service']);
        }

        return $query->latest()->paginate(10);
    }

    public function findByAccount(int $accountId)
    {
        return Beautician::where('account_id', $accountId)->first();
    }

    public function createForAccount(int $accountId, int $userId, array $data)
    {
        return Beautician::create([
            'account_id' => $accountId,
            'user_id'    => $userId,
            'name'       => $data['name'],
            'services'   => $data['services'] ?? null, // ✅ stored as array
            'country'    => $data['country'] ?? null,
            'city'       => $data['city'] ?? null,
            'address'    => $data['address'] ?? null,
            'logo'       => $data['logo'] ?? null,
            'cover'      => $data['cover'] ?? null,
            'subdomain'  => $data['subdomain'] ?? null,
        ]);
    }
}
