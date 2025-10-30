<?php

namespace App\Repositories\Eloquent;

use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceRepository
{
    public function listByAccount(int $accountId)
    {
        return Service::where('AccountId',$accountId)
            ->where('IsDeleted',0)
            ->orderBy('Name')
            ->get();
    }

    public function findByAccount(int $accountId,int $id): Service
    {
        return Service::where('AccountId',$accountId)
            ->where('Id',$id)
            ->where('IsDeleted',0)
            ->firstOrFail();
    }

    public function create(array $data): Service
    {
        $user = Auth::user();
        $createdById = $user?->bkUser?->Id ?? null;

        $depositType = match (strtolower($data['DepositType'] ?? '')) {
            'percentage' => 0,
            'fixed' => 1,
            default => null,
        };

        return Service::create([
            'AccountId'  => $user?->bkUser?->account?->Id,
            'CategoryId' => $data['CategoryId'] ?? null,
            'Name'       => $data['Name'],
            'TotalPrice' => $data['TotalPrice'] ?? 0,
            'DepositType' => $depositType,
            'Deposit'    => $data['Deposit'] ?? 0,
            'DefaultAppointmentDuration' => $data['DefaultAppointmentDuration'] ?? 0,
            'DurationUnit' => $data['DurationUnit'] ?? 'mins',
            'Description' => $data['Description'] ?? null,
            'FilePath'   => $data['FilePath'] ?? null,
            'ImagePath'  => $data['ImagePath'] ?? null,
            'DateCreated' => now(),
            'CreatedById' => $createdById,
            'IsDeleted'  => 0,
        ]);
    }

    public function update(int $accountId, int $id, array $data): Service
    {
        $row = $this->findByAccount($accountId, $id);

        $user = Auth::user();
        $modifiedById = $user?->bkUser?->Id ?? null;

        $depositType = match (strtolower($data['DepositType'] ?? '')) {
            'percentage' => 0,
            'fixed' => 1,
            default => null,
        };

        $row->update([
            'CategoryId' => $data['CategoryId'] ?? $row->CategoryId,
            'Name'       => $data['Name'] ?? $row->Name,
            'TotalPrice' => $data['TotalPrice'] ?? $row->TotalPrice,
            'DepositType' => $depositType,
            'Deposit'    => $data['Deposit'] ?? $row->Deposit,
            'DefaultAppointmentDuration' => $data['DefaultAppointmentDuration'] ?? $row->DefaultAppointmentDuration,
            'DurationUnit' => $data['DurationUnit'] ?? $row->DurationUnit,
            'Description' => $data['Description'] ?? $row->Description,
            'FilePath'   => array_key_exists('FilePath', $data) ? $data['FilePath'] : $row->FilePath,
            'ImagePath'  => array_key_exists('ImagePath', $data) ? $data['ImagePath'] : $row->ImagePath,
            'DateModified' => now(),
            'ModifiedById' => $modifiedById,
        ]);

        return $row->refresh();
    }


    public function softDelete(int $accountId,int $id): bool
    {
        $row = $this->findByAccount($accountId,$id);
        $user = Auth::user();
        $modifiedById = $user->bkUser->Id;
        $row->update([
            'IsDeleted'=>1,
            'DateModified'=>now(),
            'ModifiedById'=>$modifiedById,
        ]);
        return true;
    }

    public function softDeleteByCategory(int $accountId,int $categoryId): int
    {
        $user = Auth::user();
        $modifiedById = $user->bkUser->Id;

        return Service::where('AccountId',$accountId)
            ->where('CategoryId',$categoryId)
            ->update([
                'IsDeleted'=>1,
                'DateModified'=>now(),
                'ModifiedById'=>$modifiedById,
            ]);
    }
}
