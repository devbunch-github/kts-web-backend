<?php

namespace App\Repositories\Eloquent;

use App\Models\Income;

class IncomeRepository
{
    public function paginateForAccount($accountId, $perPage = 10)
    {
        return Income::with(['customer', 'category', 'service'])
        ->where('AccountId', $accountId)
        ->orderByDesc('DateCreated')
        ->paginate($perPage);
    }

    public function find($id)
    {
        return Income::with(['customer', 'category', 'service'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Income::create($data);
    }

    public function update($id, array $data)
    {
        $income = Income::findOrFail($id);
        $income->update($data);
        return $income;
    }

    public function delete($id)
    {
        return Income::where('Id', $id)->delete();
    }
}
