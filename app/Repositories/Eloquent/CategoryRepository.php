<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;

class CategoryRepository
{
    public function listByAccount($accountId)
    {
        return Category::where('AccountId', $accountId)
            ->where('IsActive', true)
            ->orderBy('Name')
            ->get();
    }
}
