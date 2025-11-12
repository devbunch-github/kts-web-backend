<?php

namespace App\Services;

use App\Repositories\Contracts\BeauticianRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BeauticianService
{
    protected $beauticianRepo;

    public function __construct(BeauticianRepositoryInterface $beauticianRepo)
    {
        $this->beauticianRepo = $beauticianRepo;
    }

    public function getBeauticians(array $filters)
    {
        return $this->beauticianRepo->allWithFilters($filters);
    }

    public function checkAccountExists(int $accountId): bool
    {
        return (bool) $this->beauticianRepo->findByAccount($accountId);
    }

    public function createBeautician(int $accountId, int $userId, Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'nullable|string|max:255',
            'services' => 'nullable|array',
            'services.*' => 'string|max:100',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'cover' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('beauticians/logos', 'public');
        }

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('beauticians/covers', 'public');
        }

        // ✅ explicitly add user/account IDs
        $data['account_id'] = $accountId;
        $data['user_id'] = $userId;

        // ✅ add subdomain manually if needed
        if (empty($data['subdomain']) && !empty($data['name'])) {
            $data['subdomain'] = Str::slug($data['name']) . '-' . uniqid();
        }

        return $this->beauticianRepo->createForAccount($accountId, $userId, $data);
    }

}
