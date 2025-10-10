<?php

namespace App\Repositories\Eloquent;

use App\Models\Service;

class ServiceRepository
{
    public function listByAccount(?int $accountId)
    {
        if (!$accountId) {
            return collect(); // return empty collection
        }

        return Service::where('AccountId', $accountId)
            ->orderBy('Name')
            ->get();
    }


    public function find(int $id)
    {
        return Service::findOrFail($id);
    }

    public function create(array $data)
    {
        return Service::create($data);
    }

    public function update(int $id, array $data)
    {
        $service = Service::findOrFail($id);
        $service->update($data);
        return $service;
    }

    public function delete(int $id)
    {
        Service::where('Id', $id)->delete();
    }
}
