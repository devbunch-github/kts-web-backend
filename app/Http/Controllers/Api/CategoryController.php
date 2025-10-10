<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Repositories\Eloquent\CategoryRepository;
use App\Models\User;

class CategoryController extends Controller
{
    public function __construct(protected CategoryRepository $categories) {}

    protected function currentAccountId(): ?int
    {
        // ✅ 1) Normal authenticated user
        if (Auth::check()) {
            return Auth::user()?->bkUser?->account?->Id;
        }

        // ✅ 2) Fallback for preRegister (user ID from header)
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

        $list = $this->categories->listByAccount($accountId);
        return response()->json(['data' => $list]);
    }
}
