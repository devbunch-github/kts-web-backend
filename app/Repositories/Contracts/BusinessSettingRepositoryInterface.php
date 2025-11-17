<?php

namespace App\Repositories\Contracts;

interface BusinessSettingRepositoryInterface
{
    public function findByType(int $accountId, string $type);
    public function saveOrUpdate(int $accountId, string $type, array $data);
}
