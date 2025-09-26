<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Models\Plan;

class PlanRepository implements PlanRepositoryInterface {
    public function active() {
        return Plan::where('is_active', true)->orderBy('price_minor')->get();
    }
}
