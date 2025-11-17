<?php

namespace App\Services;

use App\Repositories\Contracts\BusinessSettingRepositoryInterface;

class BusinessSettingService
{
    public function __construct(protected BusinessSettingRepositoryInterface $repo) {}

    public function get(int $accountId, string $type)
    {
        return $this->repo->findByType($accountId, $type);
    }

    public function update(int $accountId, string $type, array $data)
    {
        return $this->repo->saveOrUpdate($accountId, $type, $data);
    }
}
