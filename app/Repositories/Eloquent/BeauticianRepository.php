<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BeauticianRepositoryInterface;
use App\Models\Beautician;

class BeauticianRepository implements BeauticianRepositoryInterface
{
    public function allWithFilters(array $filters)
    {
        $query = Beautician::query();

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['service'])) {
            $query->whereHas('services', function ($q) use ($filters) {
                $q->where('name', $filters['service']);
            });
        }

        return $query->paginate(10);
    }
}
