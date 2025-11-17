<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessAdmin\Form\StoreBusinessFormRequest;
use App\Http\Requests\BusinessAdmin\Form\UpdateBusinessFormRequest;
use App\Http\Resources\BusinessFormResource;
use App\Services\BusinessFormService;
use Illuminate\Http\Request;
use Exception;

class BusinessFormController extends Controller
{
    public function __construct(private BusinessFormService $service) {}

    protected function currentAccountId(): int
    {
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    // GET /api/forms
    public function index(Request $request)
    {
        $accountId = $this->currentAccountId();
        $data = $this->service->list($accountId, perPage: $request->integer('per_page', 10));
        return BusinessFormResource::collection($data);
    }

    // GET /api/forms/{id}
    public function show(Request $request, int $id)
    {
        $accountId = $this->currentAccountId();
        $form = $this->service->toggle($accountId, $id, true); // just to ensure 404 gives consistent message
        $form = $this->service->list($accountId, 1)->getCollection()->firstWhere('id', $id)
             ?? app(\App\Repositories\Contracts\BusinessFormRepositoryInterface::class)->findForAccount($accountId, $id);

        abort_if(!$form, 404, 'Form not found.');
        return new BusinessFormResource($form->load(['questions','services']));
    }

    // POST /api/forms
    public function store(StoreBusinessFormRequest $request)
    {
        $accountId = $this->currentAccountId();

        $form = $this->service->create($accountId, $request->validated());

        return (new BusinessFormResource($form))
            ->additional(['message' => 'Form created successfully.']);
    }

    // PUT /api/forms/{id}
    public function update(UpdateBusinessFormRequest $request, int $id)
    {
        $accountId = $this->currentAccountId();
        $form = $this->service->update($accountId, $id, $request->validated());

        return (new BusinessFormResource($form))
            ->additional(['message' => 'Form updated successfully.']);
    }

    // DELETE /api/forms/{id}
    public function destroy(Request $request, int $id)
    {
        $accountId = $this->currentAccountId();
        $this->service->delete($accountId, $id);
        return response()->json(['message' => 'Form deleted.']);
    }

    // PATCH /api/forms/{id}/toggle
    public function toggle(Request $request, int $id)
    {
        $accountId = $this->currentAccountId();
        $active = (bool)$request->boolean('is_active', true);
        $form = $this->service->toggle($accountId, $id, $active);
        return (new BusinessFormResource($form))->additional(['message' => 'Status updated.']);
    }
}
