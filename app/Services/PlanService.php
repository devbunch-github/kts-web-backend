<?php

namespace App\Services;

use App\Repositories\Contracts\PlanRepositoryInterface;

class PlanService
{
    public function __construct(private PlanRepositoryInterface $repo) {}

    public function active()
    {
        return $this->repo->active();
    }
}
