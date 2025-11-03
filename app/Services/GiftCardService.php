<?php

namespace App\Services;

use App\Repositories\Contracts\GiftCardRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class GiftCardService
{
    public function __construct(private GiftCardRepositoryInterface $repo) {}

    public function index(int $accountId, array $filters)
    {
        $query = $this->repo->query()->where('account_id', $accountId)->with('service');

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($s) use ($q) {
                $s->where('title', 'like', "%{$q}%")
                  ->orWhere('code', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['status'])) {
            $today = Carbon::today();
            $status = $filters['status'];

            $query->when($status === 'active', function ($q) use ($today) {
                $q->where('is_active', true)
                    ->where(function ($x) use ($today) {
                        $x->whereNull('end_date')->orWhere('end_date', '>=', $today);
                    });
            });

            $query->when($status === 'expired', fn($q) => $q->where('end_date', '<', $today));
            $query->when($status === 'upcoming', fn($q) => $q->where('start_date', '>', $today));
        }

        return $query->latest()->paginate($filters['per_page'] ?? 10);
    }

    public function store(int $accountId, array $data)
    {
        $data['account_id'] = $accountId;

        if (request()->hasFile('image')) {
            $data['image_path'] = request()->file('image')->store('giftcards', 'public');
        }

        // Auto generate code if empty
        if (empty($data['code'])) {
            $data['code'] = strtoupper('GFT-' . substr(uniqid(), -6));
        }

        return $this->repo->create($data);
    }

    public function update(int $accountId, int $id, array $data)
    {
        $gift = $this->repo->findByAccount($accountId, $id);
        if (!$gift) throw new Exception('Gift Card not found');

        if (request()->hasFile('image')) {
            if ($gift->image_path && Storage::disk('public')->exists($gift->image_path)) {
                Storage::disk('public')->delete($gift->image_path);
            }
            $data['image_path'] = request()->file('image')->store('giftcards', 'public');
        }

        $gift->update($data);
        return $gift->refresh();
    }

    public function destroy(int $accountId, int $id)
    {
        $gift = $this->repo->findByAccount($accountId, $id);
        if (!$gift) throw new Exception('Gift Card not found');
        $gift->delete();
    }
}
