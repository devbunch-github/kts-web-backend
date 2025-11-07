<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessAdmin\StoreBusinessToDoRequest;
use App\Http\Requests\BusinessAdmin\UpdateBusinessToDoRequest;
use App\Http\Resources\BusinessToDoResource;
use App\Services\BusinessToDoService;
use Illuminate\Http\Request;

class BusinessToDoController extends Controller
{
    public function __construct(private BusinessToDoService $service) {}

    /** GET /api/business/todo?completed=0|1 */
    public function index(Request $request)
    {
        $completed = $request->has('completed') ? (bool) $request->boolean('completed') : null;
        $items = $this->service->list($completed);
        return BusinessToDoResource::collection($items);
    }

    /** POST /api/business/todo */
    public function store(StoreBusinessToDoRequest $request)
    {
        $todo = $this->service->create($request->validated());
        return new BusinessToDoResource($todo);
    }

    /** PUT /api/business/todo/{id} */
    public function update(UpdateBusinessToDoRequest $request, string $id)
    {
        $todo = $this->service->update($id, $request->validated());
        return new BusinessToDoResource($todo);
    }

    /** DELETE /api/business/todo/{id} */
    public function destroy(string $id)
    {
        $this->service->delete($id);
        return response()->json(['success' => true]);
    }

    /** PATCH /api/business/todo/{id}/toggle */
    public function toggle(string $id)
    {
        $todo = $this->service->toggle($id);
        return new BusinessToDoResource($todo);
    }
}
