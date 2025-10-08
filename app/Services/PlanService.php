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

    /**
     * Find a specific plan by ID
     */
    public function find($id)
    {
        return $this->repo->find($id);
    }
}
