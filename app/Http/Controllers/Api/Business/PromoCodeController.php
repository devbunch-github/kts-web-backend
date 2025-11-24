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

    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'service_id' => 'required|integer',
            'code'       => 'required|string|max:50',
        ]);

        $accountId = (int) $data['account_id'];
        $serviceId = (int) $data['service_id'];
        $code      = trim($data['code']);

        $today = Carbon::today()->toDateString();

        $promo = PromoCode::forAccount($accountId)
            ->where('code', $code)
            ->where('status', 1)                          // active
            ->where('service_id', $serviceId)             // 🔒 service-specific only
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

        return response()->json([
            'valid' => true,
            'data'  => [
                'id'            => $promo->id,
                'code'          => $promo->code,
                'title'         => $promo->title,
                'discount_type' => $promo->discount_type,   // percent|fixed
                'discount_value'=> (float) $promo->discount_value,
                'service_id'    => $promo->service_id,
                'start_date'    => $promo->start_date,
                'end_date'      => $promo->end_date,
            ],
        ]);
    }
}
