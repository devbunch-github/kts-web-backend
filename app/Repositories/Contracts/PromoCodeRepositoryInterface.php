<?php

namespace App\Repositories\Contracts;

use App\Models\PromoCode;

interface PromoCodeRepositoryInterface
{
    public function listByAccount(int $accountId, array $filters = []);
    public function findByAccount(int $accountId, int $id): PromoCode;
    public function createForAccount(int $accountId, array $data): PromoCode;
    public function updateForAccount(int $accountId, int $id, array $data): PromoCode;
    public function softDeleteByAccount(int $accountId, int $id): bool;
    public function codeExists(int $accountId, string $code, ?int $ignoreId = null): bool;
}
