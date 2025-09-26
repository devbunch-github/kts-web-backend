<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller {
    public function __construct(private AuthService $auth) {}

    public function register(Request $r)
    {
        $v = $r->validate([
            'name'=>'required|string|max:191',
            'business_name'=>'nullable|string|max:191',
            'email'=>'required|email|unique:users,email',
            'phone'=>'nullable|string|max:30',
            'password'=>'nullable',
        ]);
        return $this->auth->register($v);
    }

    public function login(Request $r)
    {
        $v = $r->validate([
            'email'=>'required|email'
        ]);
        return $this->auth->login($v['email']);
    }
}

