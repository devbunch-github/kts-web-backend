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
            'account_id'  => 'required|integer',
            'code'        => 'required|string|max:50',
            'customer_id' => 'required|integer',
        ], [
            'customer_id.required' => 'Please login/signup to use gift cards.',
        ]);

        $accountId  = (int) $data['account_id'];
        $code       = trim($data['code']);
        $today      = now();

        $giftCard = GiftCard::where('account_id', $accountId)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$giftCard) {
            return response()->json([
                'valid'   => false,
                'message' => 'Invalid or inactive gift card.',
            ], 404);
        }

        $purchase = GiftCardPurchase::where('AccountId', $accountId)
            ->where('GiftCardId', $giftCard->Id ?? $giftCard->id)
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

        if ((float) $purchase->Amount - (float) $purchase->UsedAmount <= 0) {
            return response()->json([
                'valid'   => false,
                'message' => 'This gift card has no remaining balance.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'data'  => [
                'id'         => $purchase->Id,
                'code'       => $purchase->Code,
                'amount'     => (float) $purchase->Amount,     // original
                'used'       => (float) $purchase->UsedAmount, // used total
                'remaining'  => $purchase->remaining,
                'remaining_amount'  => (float) $purchase->Amount - (float) $purchase->UsedAmount,
                'expires_at' => $purchase->ExpiresAt,
            ],
        ]);
    }

    /**
     * Admin: usage history for gift card definition (GiftCard)
     * Route: GET /api/gift-cards/{id}/usages
     */
    public function usages(Request $request, $id)
    {
        $accountId = $this->currentAccountId();

        $giftCard = GiftCard::where('account_id', $accountId)->findOrFail($id);

        // Pagination parameters
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        // Paginated purchases
        $purchases = GiftCardPurchase::with(['customer'])
            ->where('AccountId', $accountId)
            ->where('GiftCardId', $giftCard->Id ?? $giftCard->id)
            ->orderBy('Id', 'DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        // Format purchases with usage rows
        $purchases->getCollection()->transform(function ($purchase) {
            $purchase->usage_rows = $purchase->usages()
                ->with('appointment')
                ->orderByDesc('created_at')
                ->get();
            return $purchase;
        });

        return response()->json([
            'gift_card' => $giftCard,
            'purchases' => $purchases->items(),
            'meta'      => [
                'current_page' => $purchases->currentPage(),
                'last_page'    => $purchases->lastPage(),
                'per_page'     => $purchases->perPage(),
                'total'        => $purchases->total(),
            ]
        ]);
    }

}
