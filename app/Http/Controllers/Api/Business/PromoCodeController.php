<?php

namespace App\Http\Controllers\Api\Business;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromoCodeResource;
use App\Services\PromoCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Exception;
use Illuminate\Support\Carbon;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;

class PromoCodeController extends Controller
{
    public function __construct(private PromoCodeService $service) {}

    protected function currentAccountId(): int
    {
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

    /**
     * PUBLIC: validate promo code before applying
     * Route: GET public/promo/validate
     */
    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'account_id'  => 'required|integer',
            'service_id'  => 'required|integer',
            'code'        => 'required|string|max:50',
            'customer_id' => 'required|integer', // ⬅ allow if logged in on FE
        ], [
            'customer_id.required' => 'Please login/signup to use promo codes.',
        ]);

        $accountId  = (int) $data['account_id'];
        $serviceId  = (int) $data['service_id'];
        $code       = trim($data['code']);
        $customerId = $data['customer_id'] ?? null;

        $today = Carbon::today()->toDateString();

        $promo = PromoCode::forAccount($accountId)
            ->where('code', $code)
            ->where('status', 1)
            ->where(function ($q) use ($serviceId) {
                $q->whereNull('service_id')
                  ->orWhere('service_id', $serviceId);
            })
            ->whereDate('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhereDate('end_date', '>=', $today);
            })
            ->first();

        if (!$promo) {
            return response()->json([
                'valid'   => false,
                'message' => 'Invalid, inactive or expired promo code.',
            ], 404);
        }

        // ---------- GLOBAL USAGE LIMIT ----------
        $globalUsed = PromoCodeUsage::where('promo_code_id', $promo->id)->count();

        if ($globalUsed >= $promo->usage_limit_global) {
            return response()->json([
                'valid'   => false,
                'message' => 'This promo code has reached its maximum usage limit.',
            ], 422);
        }

        // ---------- PER-CUSTOMER USAGE LIMIT ----------
        $customerUsed = 0;
        if ($customerId) {
            $customerUsed = PromoCodeUsage::where('promo_code_id', $promo->id)
                ->where('customer_id', $customerId)
                ->count();

            if ($customerUsed >= $promo->usage_limit_per_customer) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'You have already used this promo code.',
                ], 422);
            }
        }

        return response()->json([
            'valid' => true,
            'data'  => [
                'id'                   => $promo->id,
                'code'                 => $promo->code,
                'title'                => $promo->title,
                'discount_type'        => $promo->discount_type,   // percent|fixed
                'discount_value'       => (float) $promo->discount_value,
                'service_id'           => $promo->service_id,
                'start_date'           => $promo->start_date,
                'end_date'             => $promo->end_date,
                'usage_limit_global'   => $promo->usage_limit_global,
                'usage_limit_customer' => $promo->usage_limit_per_customer,
                'used_global'          => $globalUsed,
                'used_customer'        => $customerUsed,
                'remaining_global'     => max(0, $promo->usage_limit_global - $globalUsed),
                'remaining_customer'   => max(0, $promo->usage_limit_per_customer - $customerUsed),
            ],
        ]);
    }

    /**
     * For admin: list usages of a promo code
     * Route: GET /api/promo-codes/{id}/usages
     */
    public function usages(Request $request, $id)
    {
        $accountId = $this->currentAccountId();

        $promo = PromoCode::forAccount($accountId)->findOrFail($id);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $usages = PromoCodeUsage::with(['customer', 'appointment'])
            ->where('promo_code_id', $promo->id)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'promo'  => new PromoCodeResource($promo),
            'usages' => $usages->items(),
            'meta'   => [
                'current_page' => $usages->currentPage(),
                'last_page'    => $usages->lastPage(),
                'per_page'     => $usages->perPage(),
                'total'        => $usages->total(),
            ]
        ]);
    }

}
