<?php

namespace App\Repositories\Client;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Customer;
use App\Models\GiftCardPurchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClientRepository implements ClientRepositoryInterface
{
    public function getAppointmentsByClient($clientId)
    {
        $clientData = User::findOrFail($clientId);
        $customerId = Customer::where('Email', $clientData->email)->value('Id');

        $now = Carbon::now();

        return Appointment::with('service:Id,Name')
            ->where('CustomerId', $customerId)
            ->orderBy('StartDateTime', 'desc')
            ->get()
            ->map(function ($a) use ($now) {
                $start = Carbon::parse($a->StartDateTime);

                // 💳 Amount & payment – adjust to your real logic
                $amount = (float) ($a->FinalAmount ?? $a->Cost ?? 0);
                $payBalance = 0.0;
                $payStatus = 'Paid'; // or derive from your fields

                // 📅 Status flags
                $status = 'upcoming';
                if (!is_null($a->CancellationDate) || $a->Status === 'cancelled') {
                    $status = 'cancelled';
                } elseif ($start->lt($now)) {
                    $status = 'completed';
                }

                return [
                    'id'              => $a->Id,
                    'service_name'    => $a->service->Name ?? 'Service',
                    'amount'          => $amount,
                    'appointment_date'=> $start->format('Y-m-d'),
                    'appointment_time'=> $start->format('H:i'),
                    'pay_status'      => $payStatus,      // Paid / Partially Paid / Unpaid
                    'pay_balance'     => $payBalance,     // e.g. 5.00
                    'status'          => $status,         // upcoming/completed/cancelled

                    // Flags to simplify UI logic
                    'can_cancel'      => $status === 'upcoming',
                    'can_reschedule'  => $status === 'upcoming',
                    'can_review'      => $status === 'completed',
                ];
            })
            ->values();
    }


    public function getPurchasedGiftCards($clientUserId)
    {
        $user = User::findOrFail($clientUserId);

        // find CustomerId via email
        $customerId = Customer::where('Email', $user->email)->value('Id');

        if (!$customerId) return collect([]);

        return GiftCardPurchase::with(['giftCard'])
            ->where('CustomerId', $customerId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($gc) {
                $remaining = (float)$gc->Amount - (float)$gc->UsedAmount;

                return [
                    'id'            => $gc->Id,
                    'title'         => $gc->giftCard->title ?? 'Gift Card',
                    'date'          => optional($gc->created_at)->format('d-m-Y'),
                    'total_amount'  => (float)$gc->Amount,
                    'redeemed'      => (float)$gc->UsedAmount,
                    'remaining'     => $remaining,
                ];
            });
    }

    public function cancelAppointment($clientId, $appointmentId, $reason)
    {
        $user = User::findOrFail($clientId);
        $customerId = Customer::where('Email', $user->email)->value('Id');

        $appointment = Appointment::where('Id', $appointmentId)
            ->where('CustomerId', $customerId)
            ->first();

        if (! $appointment) {
            return false;
        }

        $appointment->Status = 'cancelled';
        $appointment->CancellationReason = $reason ?? null;
        $appointment->CancellationDate = Carbon::now();
        $appointment->save();

        return true;
    }

    public function rescheduleAppointment($clientId, $appointmentId, $date, $time)
    {
        $user = User::findOrFail($clientId);
        $customerId = Customer::where('Email', $user->email)->value('Id');

        $appointment = Appointment::where('Id', $appointmentId)
            ->where('CustomerId', $customerId)
            ->first();

        if (! $appointment) {
            return false;
        }

        $start = Carbon::parse("$date $time");
        $appointment->StartDateTime = $start;

        // Simple 1-hour slot – tweak to match your duration logic
        $appointment->EndDateTime = (clone $start)->addHour();

        $appointment->save();

        return true;
    }

    public function leaveReview($clientId, $appointmentId, $rating, $review)
    {
        $user = User::findOrFail($clientId);
        $customerId = Customer::where('Email', $user->email)->value('Id');

        DB::table('AppointmentReviews')->insert([
            'AppointmentId' => $appointmentId,
            'CustomerId'    => $customerId,
            'Rating'        => $rating,
            'Review'        => $review,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return true;
    }


}
