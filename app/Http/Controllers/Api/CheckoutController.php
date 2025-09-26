<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CheckoutService;

class CheckoutController extends Controller {
    public function __construct(private CheckoutService $checkout) {}

    public function create(Request $request)
    {
        $data = $request->validate([
            'plan_id'=>'required|exists:plans,id',
            'email'=>'nullable|email'
        ]);
        return $this->checkout->createIntent($data['plan_id'], $data['email'] ?? null);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'plan_id'=>'required|exists:plans,id',
            'user_id'=>'required|exists:users,id'
        ]);
        return $this->checkout->confirm($data['plan_id'], $data['user_id']);
    }
}
