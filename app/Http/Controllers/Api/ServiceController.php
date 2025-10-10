<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Repositories\Eloquent\ServiceRepository;
use App\Models\User;

class ServiceController extends Controller
{
    public function __construct(protected ServiceRepository $services) {}

    protected function currentAccountId(): ?int
    {
        if (Auth::check()) {
            return Auth::user()?->bkUser?->account?->Id;
        }

        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
            $user = User::find($userId);
            return $user?->bkUser?->account?->Id;
        }

        return null;
    }

    public function index(Request $request)
    {
        $accountId = $this->currentAccountId();

        if (!$accountId) {
            return response()->json(['message' => 'No account found'], 404);
        }

        $services = $this->services->listByAccount($accountId);
        return response()->json(['data' => $services]);
    }
}
