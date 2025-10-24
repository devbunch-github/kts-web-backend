<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Repositories\Eloquent\ServiceRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Models\User;
use App\Http\Requests\BusinessAdmin\ServiceStoreRequest;
use App\Http\Requests\BusinessAdmin\ServiceUpdateRequest;

class ServiceController extends Controller
{
    public function __construct(
        protected ServiceRepository $services,
        protected CategoryRepository $categories
    ) {}

    protected function currentAccountId(): ?int
    {
        if (Auth::check()) return Auth::user()?->bkUser?->account?->Id;

        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
        $user = User::find($userId);
            return $user?->bkUser?->account?->Id;
        }
        return null;
    }

    public function index(Request $request)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        return response()->json(['data'=>$this->services->listByAccount($accId)]);
    }

    public function show($id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        return response()->json(['data'=>$this->services->findByAccount($accId,(int)$id)]);
    }

    public function store(ServiceStoreRequest $request)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        if ($request->filled('CategoryId')) {
            try { $this->categories->findByAccount($accId,(int)$request->input('CategoryId')); }
            catch (\Throwable $e) { return response()->json(['message'=>'Invalid category'],422); }
        }

        $row = $this->services->create($request->validated());
        return response()->json(['data'=>$row],201);
    }

    public function update(ServiceUpdateRequest $request,$id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        if ($request->filled('CategoryId')) {
            try { $this->categories->findByAccount($accId,(int)$request->input('CategoryId')); }
            catch (\Throwable $e) { return response()->json(['message'=>'Invalid category'],422); }
        }

        $row = $this->services->update($accId,(int)$id,$request->validated());
        return response()->json(['data'=>$row]);
    }

    public function destroy($id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        $this->services->softDelete($accId,(int)$id);
        return response()->json(['message'=>'Deleted']);
    }
}
