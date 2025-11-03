<?php

namespace App\Repositories\Eloquent;

use App\Models\GiftCard;
use App\Repositories\Contracts\GiftCardRepositoryInterface;

class GiftCardRepository implements GiftCardRepositoryInterface
{
    public function __construct(private GiftCard $model) {}

    public function query()
    {
        return $this->model->newQuery();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function findByAccount(int $accountId, int $id)
    {
        return $this->model
            ->where('account_id', $accountId)
            ->where('id', $id)
            ->with('service')
            ->firstOrFail();
    }
}
