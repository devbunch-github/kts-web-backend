<?php

namespace App\Services;

use App\Repositories\Contracts\LoyaltyCardRepository;

class LoyaltyCardService
{
    public function __construct(private LoyaltyCardRepository $repo) {}

    public function getSettings(int $accountId) {
        return $this->repo->getByAccount($accountId);
    }

    public function saveSettings(int $accountId, array $payload) {
        return $this->repo->upsert($accountId, $payload);
    }
}
