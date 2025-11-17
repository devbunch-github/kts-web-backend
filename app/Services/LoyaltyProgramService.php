<?php

namespace App\Services;

use App\Repositories\Contracts\LoyaltyProgramRepository;

class LoyaltyProgramService
{
    public function __construct(private LoyaltyProgramRepository $repo) {}

    public function getSettings(int $accountId) {
        return $this->repo->getByAccount($accountId);
    }

    public function saveSettings(int $accountId, array $payload) {
        return $this->repo->upsert($accountId, $payload);
    }

    public function summary(int $accountId): array {
        return $this->repo->summary($accountId);
    }
}
