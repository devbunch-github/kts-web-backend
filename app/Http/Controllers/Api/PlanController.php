<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PlanService;
use App\Http\Resources\PlanResource;
use Symfony\Component\HttpFoundation\Response;

class PlanController extends Controller {
    public function __construct(private PlanService $plans) {}

    public function index()
    {
        return PlanResource::collection($this->plans->active());
    }

    /**
     * Return single plan by ID
     */
    public function show($id)
    {
        $plan = $this->plans->find($id);

        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new PlanResource($plan);
    }

}
