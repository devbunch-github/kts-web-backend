<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PlanService;
use App\Http\Resources\PlanResource;

class PlanController extends Controller {
    public function __construct(private PlanService $plans) {}

    public function index()
    {
        return PlanResource::collection($this->plans->active());
    }
}
