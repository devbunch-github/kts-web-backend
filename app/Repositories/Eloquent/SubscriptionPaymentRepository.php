<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\SubscriptionPaymentRepositoryInterface;
use App\Models\SubscriptionPayment;

class SubscriptionPaymentRepository
{
    public function create(array $data)
    {
        return SubscriptionPayment::create($data);
    }
}

