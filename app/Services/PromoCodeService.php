<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Repositories\Contracts\PromoCodeRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PromoCodeService
{
    public function __construct(
        protected PromoCodeRepositoryInterface $repo
    ) {}

    public function index(int $accountId, array $filters = [])
    {
        return $this->repo->listByAccount($accountId, $filters);
    }

    public function store(int $accountId, array $payload): PromoCode
    {
        $this->validate($accountId, $payload);
        $payload['status'] = isset($payload['status']) ? (int)$payload['status'] : 1;
        return $this->repo->createForAccount($accountId, $payload);
    }

    public function update(int $accountId, int $id, array $payload): PromoCode
    {
        $this->validate($accountId, $payload, $id);
        if (isset($payload['status'])) $payload['status'] = (int)$payload['status'];
        return $this->repo->updateForAccount($accountId, $id, $payload);
    }

    public function destroy(int $accountId, int $id): bool
    {
        return $this->repo->softDeleteByAccount($accountId, $id);
    }

    protected function validate(int $accountId, array $data, ?int $id = null): void
    {
        $v = Validator::make($data, [
            'title'          => 'required|string|max:191',
            'code'           => 'required|string|max:50',
            'service_id'     => 'nullable|integer|exists:services,Id', // your legacy Service PK is 'Id'
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0.01',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'status'         => 'nullable|in:0,1',
            'notes'          => 'nullable|string',
        ]);

        if ($v->fails()) {
            throw new ValidationException($v);
        }

        // unique per account
        if ($this->repo->codeExists($accountId, $data['code'], $id)) {
            throw ValidationException::withMessages(['code' => 'Code already exists for this account.']);
        }

        // Optional: clamp percent to 0..100
        if (($data['discount_type'] ?? null) === 'percent' && ($data['discount_value'] ?? 0) > 100) {
            throw ValidationException::withMessages(['discount_value' => 'Percent cannot exceed 100.']);
        }

        // Optional: auto status toggle by dates (keep manual status otherwise)
        if (!isset($data['status'])) {
            $today = Carbon::today();
            $sd = Carbon::parse($data['start_date']);
            $ed = isset($data['end_date']) ? Carbon::parse($data['end_date']) : null;
            $data['status'] = ($sd <= $today && (is_null($ed) || $ed >= $today)) ? 1 : 0;
        }
    }
}
