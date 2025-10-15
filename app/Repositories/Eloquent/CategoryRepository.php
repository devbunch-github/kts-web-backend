<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryRepository
{
    public function listByAccount(int $accountId)
    {
        return Category::where('AccountId',$accountId)
            ->where('IsActive',1)
            ->orderBy('Name')
            ->get();
    }

    public function findByAccount(int $accountId,int $id): Category
    {
        return Category::where('AccountId',$accountId)
            ->where('Id',$id)
            ->firstOrFail();
    }

    public function create(array $data): Category
    {
        $user = Auth::user();
        $createdById = $user->bkUser->Id;

        return Category::create([
            'Name'        => $data['Name'],
            'AccountId'   => $user?->bkUser?->account?->Id,
            'Description' => $data['Description'] ?? null,
            'CreatedById' => $createdById,
            'IsActive'    => $data['IsActive'] ?? 1,
        ]);
    }

    public function update(int $accountId,int $id,array $data): Category
    {
        $row = $this->findByAccount($accountId,$id);
        $row->update([
            'Name'=>$data['Name'] ?? $row->Name,
            'Description' => $data['Description'] ?? null,
            'IsActive'=>$data['IsActive'] ?? $row->IsActive,
        ]);
        return $row->refresh();
    }

    public function deleteHard(int $accountId,int $id): bool
    {
        $row = $this->findByAccount($accountId,$id);
        $row->delete();
        return true;
    }
}
