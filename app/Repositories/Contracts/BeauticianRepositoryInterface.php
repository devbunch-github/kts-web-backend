<?php

namespace App\Repositories\Contracts;

interface BeauticianRepositoryInterface
{
    public function allWithFilters(array $filters);
}
