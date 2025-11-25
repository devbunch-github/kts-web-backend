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
use App\Models\GiftCardPurchase;
use App\Models\GiftCard;
use Illuminate\Support\Carbon;

class GiftCardController extends Controller
{
    public function __construct(private GiftCardService $service) {}

    protected function currentAccountId($accountId = null): int
    {
        if($accountId == null) {
            // mirror your PromoCode pattern exactly
            return auth()->user()?->bkUser?->account->Id ?? throw new Exception('No account found');
        } else {
            return $accountId;
        }
        
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

    public function show(int $id, Request $request)
    {
        $accountId = $this->currentAccountId($request->account_id);
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

    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'code'       => 'required|string|max:50',
        ]);

        $accountId = (int) $data['account_id'];
        $code = trim($data['code']);
        $today = now();

        $giftCard = GiftCard::where('account_id', $accountId)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if(!$giftCard) {
            return response()->json([
                'valid'   => false,
                'message' => 'Invalid or inactive gift card.',
            ], 404);
        }

        $purchase = GiftCardPurchase::where('AccountId', $accountId)
            ->where('GiftCardId', $giftCard->id)
            ->where('PaymentStatus', 'paid')
            ->where(function ($q) use ($today) {
                $q->whereNull('ExpiresAt')
                ->orWhere('ExpiresAt', '>=', $today);
            })
            ->first();

        if (!$purchase) {
            return response()->json([
                'valid'   => false,
                'message' => 'Invalid, inactive, unpaid or expired gift card.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'data'  => [
                'id'        => $purchase->Id,
                'code'      => $giftCard->code,
                'amount'    => (float) $purchase->Amount, // original amount
                'remaining' => (float) $purchase->Amount, // you must later track used amount
                'expires_at'=> $purchase->ExpiresAt,
            ],
        ]);
    }


}
