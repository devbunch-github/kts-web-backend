<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessAdmin\GiftCardRequest;
use App\Http\Resources\GiftCardResource;
use App\Services\GiftCardService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;
use App\Repositories\Contracts\GiftCardRepositoryInterface;

class GiftCardController extends Controller
{
    public function __construct(private GiftCardService $service) {}

    protected function currentAccountId(): int
    {
        // mirror your PromoCode pattern exactly
        return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
    }

    public function index(Request $request)
    {
        $accountId = $this->currentAccountId();
        $list = $this->service->index($accountId, $request->only('status', 'q', 'per_page'));
        return GiftCardResource::collection($list);
    }

    public function store(GiftCardRequest $request)
    {
        $accountId = $this->currentAccountId();
        $gift = $this->service->store($accountId, $request->all());
        return new GiftCardResource($gift);
    }

    public function show(int $id)
    {
        $accountId = $this->currentAccountId();
        $gift = app(GiftCardRepositoryInterface::class)
            ->findByAccount($accountId, $id);
        return new GiftCardResource($gift);
    }

    public function update(GiftCardRequest $request, int $id)
    {
        $accountId = $this->currentAccountId();
        $gift = $this->service->update($accountId, $id, $request->all());
        return new GiftCardResource($gift);
    }

    public function destroy(int $id)
    {
        $accountId = $this->currentAccountId();
        $this->service->destroy($accountId, $id);
        return response()->json(['success' => true], Response::HTTP_OK);
    }

    public function publicList($accountId)
    {
        $repo = app(GiftCardRepositoryInterface::class);
        $cards = $repo->listByAccount($accountId);
        return GiftCardResource::collection($cards);
    }

}
