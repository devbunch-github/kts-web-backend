<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\ServiceRepository;
use App\Models\User;
use App\Http\Requests\BusinessAdmin\CategoryStoreRequest;
use App\Http\Requests\BusinessAdmin\CategoryUpdateRequest;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepository $categories,
        protected ServiceRepository $services
    ) {}

    protected function currentAccountId(): ?int
    {
        if (Auth::check()) return Auth::user()?->bkUser?->account?->Id;

        $userId = request()->header('X-User-Id') ?? request('user_id');
        if ($userId) {
            $u = User::find($userId);
            return $u?->bkUser?->account?->Id;
        }
        return null;
    }

    public function index()
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        return response()->json(['data'=>$this->categories->listByAccount($accId)]);
    }

    public function show($id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        return response()->json(['data'=>$this->categories->findByAccount($accId,(int)$id)]);
    }

    public function store(CategoryStoreRequest $request)
    {
        $row = $this->categories->create($request->validated());
        return response()->json(['data'=>$row],201);
    }

    public function update(CategoryUpdateRequest $request, $id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        $row = $this->categories->update($accId,(int)$id,$request->validated());
        return response()->json(['data'=>$row]);
    }

    public function destroy($id)
    {
        $accId = $this->currentAccountId();
        if (!$accId) return response()->json(['message'=>'No account found'],404);

        // popup says: removing category removes all services => soft delete services, then delete category
        $this->services->softDeleteByCategory($accId,(int)$id);
        $this->categories->deleteHard($accId,(int)$id);

        return response()->json(['message'=>'Deleted']);
    }
}
