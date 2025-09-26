<?php

namespace App\Services;

use App\Repositories\Contracts\BeauticianRepositoryInterface;

class BeauticianService
{
    protected $beauticianRepo;

    public function __construct(BeauticianRepositoryInterface $beauticianRepo)
    {
        $this->beauticianRepo = $beauticianRepo;
    }

    public function getBeauticians(array $filters)
    {
        return $this->beauticianRepo->allWithFilters($filters);
    }
}
