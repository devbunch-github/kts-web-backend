<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'uid' => $sub->user?->id ?? 0,
                    'name' => $sub->user?->name ?? 'N/A',
                    'email' => $sub->user?->email ?? 'N/A',
                    'smsPackage' => $sub->status === 'active' ? 'Active' : 'Inactive',
                    'smsUsage' => '0/0', // Placeholder, can be replaced with actual usage data
                    'subscription' => $sub->plan?->name ?? 'N/A',
                    'payment_provider' => $sub->payment_provider ?? 'N/A',
                    'payment_reference' => $sub->payment_reference ?? 'N/A',
                    'status' => ucfirst($sub->status ?? 'unknown'),
                    'starts_at' => $sub->starts_at ? $sub->starts_at->format('Y-m-d') : null,
                    'ends_at' => $sub->ends_at ? $sub->ends_at->format('Y-m-d') : null,
                    'created_at' => $sub->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }
}
