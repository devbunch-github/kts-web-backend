<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BeauticianService;
use App\Http\Resources\BeauticianResource;
use Illuminate\Http\Request;

class BeauticianController extends Controller
{
    protected $beauticianService;

    public function __construct(BeauticianService $beauticianService)
    {
        $this->beauticianService = $beauticianService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['category', 'service']);
        $beauticians = $this->beauticianService->getBeauticians($filters);

        return BeauticianResource::collection($beauticians);
    }
}
