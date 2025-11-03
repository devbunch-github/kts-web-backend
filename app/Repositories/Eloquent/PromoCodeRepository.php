<?php

namespace App\Repositories\Eloquent;

use App\Models\PromoCode;
use App\Repositories\Contracts\PromoCodeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class PromoCodeRepository implements PromoCodeRepositoryInterface
{
    public function listByAccount(int $accountId, array $filters = [])
    {
        try {
            $q = PromoCode::with('service')
                ->forAccount($accountId)
                ->orderByDesc('id');

            $today = Carbon::today()->toDateString();

            // 🧩 Status Filters
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                switch ($filters['status']) {
                    case 'active':
                        $q->where('status', 1)
                          ->whereDate('start_date', '<=', $today)
                          ->where(function ($s) use ($today) {
                              $s->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                          });
                        break;

                    case 'inactive':
                        $q->where('status', 0);
                        break;

                    case 'expired':
                        $q->whereNotNull('end_date')->whereDate('end_date', '<', $today);
                        break;

                    case 'upcoming':
                        $q->whereDate('start_date', '>', $today);
                        break;
                }
            }

            // 🧩 Search Filter
            if (!empty($filters['q'])) {
                $search = $filters['q'];
                $q->where(function ($s) use ($search) {
                    $s->where('title', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            return $q->paginate($filters['per_page'] ?? 10);

        } catch (Exception $e) {
            Log::error('PromoCodeRepository@listByAccount: '.$e->getMessage());
            throw new Exception('Unable to fetch promo codes.');
        }
    }


    public function findByAccount(int $accountId, int $id): PromoCode
    {
        try {
            return PromoCode::forAccount($accountId)->with('service')->findOrFail($id);
        } catch (Exception $e) {
            Log::error("PromoCodeRepository@findByAccount($id): ".$e->getMessage());
            throw new Exception('Promo code not found.');
        }
    }

    public function createForAccount(int $accountId, array $data): PromoCode
    {
        try {
            $user = Auth::user();
            $data['account_id']  = $accountId;
            $data['created_by_id'] = $user?->bkUser?->Id ?? null;
            $data['date_created']  = now();

            return PromoCode::create($data);
        } catch (Exception $e) {
            Log::error('PromoCodeRepository@createForAccount: '.$e->getMessage());
            throw new Exception('Failed to create promo code.');
        }
    }

    public function updateForAccount(int $accountId, int $id, array $data): PromoCode
    {
        try {
            $promo = $this->findByAccount($accountId, $id);
            $user = Auth::user();

            $data['modified_by_id'] = $user?->bkUser?->Id ?? null;
            $data['date_modified']  = now();

            $promo->update($data);
            return tap($promo)->refresh();
        } catch (Exception $e) {
            Log::error("PromoCodeRepository@updateForAccount($id): ".$e->getMessage());
            throw new Exception('Failed to update promo code.');
        }
    }

    public function softDeleteByAccount(int $accountId, int $id): bool
    {
        try {
            $promo = $this->findByAccount($accountId, $id);
            $promo->delete();
            return true;
        } catch (Exception $e) {
            Log::error("PromoCodeRepository@softDeleteByAccount($id): ".$e->getMessage());
            throw new Exception('Failed to delete promo code.');
        }
    }

    public function codeExists(int $accountId, string $code, ?int $ignoreId = null): bool
    {
        $q = PromoCode::forAccount($accountId)->where('code', $code);
        if ($ignoreId) $q->where('id', '!=', $ignoreId);
        return $q->exists();
    }
}
