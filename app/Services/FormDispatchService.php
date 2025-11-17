<?php

namespace App\Services;

use App\Models\BusinessForm;

class FormDispatchService
{
    /**
     * Returns forms that should be completed for a given Account/Service/Customer.
     * For "once", check if customer has any submission for that form.
     */
    public function formsForAppointment(string $accountId, int $serviceId, int $customerId)
    {
        $query = BusinessForm::where('AccountId',$accountId)
            ->where('is_active', true)
            ->whereHas('services', fn($q)=>$q->where('service_id',$serviceId));

        return $query->get()->filter(function($form) use ($customerId) {
            if ($form->frequency === 'every_booking') return true;

            // once: check if any submission already exists by this customer
            return !$form->submissions()
                ->where('customer_id',$customerId)
                ->exists();
        })->values();
    }
}
