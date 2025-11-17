<?php

namespace App\Repositories\Contracts;

interface GiftCardRepositoryInterface
{
    public function query();
    public function create(array $data);
    public function findByAccount(int $accountId, int $id);
    public function listByAccount($accountId);
}
