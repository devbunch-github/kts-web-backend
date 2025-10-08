<?php

namespace App\Repositories\Contracts;

interface PlanRepositoryInterface {
    public function active();
    public function find($id);
}
