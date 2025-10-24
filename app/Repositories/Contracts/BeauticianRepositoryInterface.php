<?php

namespace App\Repositories\Contracts;

interface BeauticianRepositoryInterface
{
    public function allWithFilters(array $filters);
    public function findByAccount(int $accountId);
    public function createForAccount(int $accountId, int $userId, array $data);
}
