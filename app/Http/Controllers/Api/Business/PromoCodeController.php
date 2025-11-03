<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromoCodeResource;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;

class PromoCodeController extends Controller
{
    public function __construct(private PromoCodeService $service) {}

    protected function currentAccountId(): int
    {
        // mirror your pattern
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function index(Request $request)
    {
        $accountId = $this->currentAccountId();
        $list = $this->service->index($accountId, $request->only('status','q','per_page'));
        return PromoCodeResource::collection($list);
    }

    public function store(Request $request)
    {
        $accountId = $this->currentAccountId();
        $promo = $this->service->store($accountId, $request->all());
        return new PromoCodeResource($promo);
    }

    public function show(int $id)
    {
        $accountId = $this->currentAccountId();
        $promo = app(\App\Repositories\Contracts\PromoCodeRepositoryInterface::class)->findByAccount($accountId, $id);
        return new PromoCodeResource($promo);
    }

    public function update(Request $request, int $id)
    {
        $accountId = $this->currentAccountId();
        $promo = $this->service->update($accountId, $id, $request->all());
        return new PromoCodeResource($promo);
    }

    public function destroy(int $id)
    {
        $accountId = $this->currentAccountId();
        $this->service->destroy($accountId, $id);
        return response()->json(['success' => true], Response::HTTP_OK);
    }
}
