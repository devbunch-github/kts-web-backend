<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Models\Subscription;

class SubscriptionRepository
{
    public function create(array $data)
    {
        return Subscription::create($data);
    }

    public function updateStatus($reference, $status, $dates = [])
    {
        $sub = Subscription::where('payment_reference',$reference)->first();
        if ($sub) {
            $sub->status = $status;
            if (isset($dates['starts_at'])) $sub->starts_at = $dates['starts_at'];
            if (isset($dates['ends_at'])) $sub->ends_at = $dates['ends_at'];
            $sub->save();
        }
        return $sub;
    }
}

