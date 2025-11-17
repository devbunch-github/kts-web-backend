<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BeauticianService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BeauticianController extends Controller
{
    public function __construct(protected BeauticianService $beauticianService) {}

    // Public listing
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'service', 'account_id', 'subdomain']);
        $beauticians = $this->beauticianService->getBeauticians($filters);

        return response()->json([
            'data' => $beauticians->items(),
            'meta' => [
                'current_page' => $beauticians->currentPage(),
                'last_page' => $beauticians->lastPage(),
            ],
        ]);
    }

    // Check setup status
    public function check()
    {
        $user = Auth::user();
        $accountId = $user?->bkUser?->account?->Id;
        if (!$accountId) return response()->json(['exists' => false]);

        $exists = $this->beauticianService->checkAccountExists($accountId);
        return response()->json(['exists' => $exists]);
    }

    // Setup
    public function setup(Request $request)
    {
        $user = Auth::user();
        $accountId = $user?->bkUser?->account?->Id;

        if (!$accountId) {
            return response()->json(['message' => 'No account found'], 404);
        }

        $beautician = $this->beauticianService->createBeautician($accountId, $user->id, $request);

        return response()->json([
            'message' => 'Business setup completed successfully!',
            'data' => $beautician,
        ]);
    }
}
