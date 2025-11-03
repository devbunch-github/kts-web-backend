<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountEmailTemplateRequest;
use App\Http\Resources\AccountEmailTemplateResource;
use App\Services\AccountEmailTemplateService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailMessageController extends Controller
{
    public function __construct(private AccountEmailTemplateService $service) {}

    protected function currentAccountId(): int
    {
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function index(Request $request)
    {
        $accountId = $this->currentAccountId();
        $list = $this->service->list($accountId);
        return AccountEmailTemplateResource::collection($list);
    }

    public function show(int $id)
    {
        $accountId = $this->currentAccountId();
        $template = $this->service->find($id, $accountId);
        return new AccountEmailTemplateResource($template);
    }

    public function update(UpdateAccountEmailTemplateRequest $request, int $id)
    {
        $accountId = $this->currentAccountId();
        $updated = $this->service->update($id, $request->validated(), $accountId);
        return new AccountEmailTemplateResource($updated);
    }
}
