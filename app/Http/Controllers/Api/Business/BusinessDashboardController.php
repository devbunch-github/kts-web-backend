<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Business\BusinessDashboardService;

class BusinessDashboardController extends Controller
{
    public function __construct(private BusinessDashboardService $service) {}

    public function summary(Request $request)
    {
        $accId = $this->accountId();
        return response()->json([
            'success' => true,
            'data' => $this->service->getMonthlySummary($accId),
        ]);
    }

    public function appointments(Request $request)
    {
        $accId = $this->accountId();
        $start = $request->query('start'); // 'YYYY-MM-DD'
        $end   = $request->query('end');   // 'YYYY-MM-DD'

        return response()->json([
            'success' => true,
            'data' => $this->service->getCalendarEvents($accId, $start, $end),
        ]);
    }

    /**
     * Mirrors your pattern from IncomeController: auth → bkUser → account → Id,
     * and supports X-User-Id header for pre-register flows.
     */
    private function accountId(): ?int
    {
        if (auth()->check()) {
            return auth()->user()?->bkUser?->account?->Id;
        }

        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            return $user?->bkUser?->account?->Id;
        }

        return null;
    }
}
