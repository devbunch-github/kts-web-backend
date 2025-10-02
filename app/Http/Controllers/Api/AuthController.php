<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Str;

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
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
            'remember' => 'boolean',
        ]);
        return $this->auth->login($v['email'], $v['password'], (bool)($v['remember'] ?? false));
    }

    public function preRegister(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:users,email',
            'name'  => 'required|string|max:191',
            'country' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'country' => $data['country'] ?? null,
            'password' => bcrypt(Str::random(12)), // temp random password
            'status' => 'pending',
        ]);

        return response()->json([
            'id'    => $user->id,
            'email' => $user->email,
        ]);
    }

    public function setPassword(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::findOrFail($data['user_id']);
        $user->password = bcrypt($data['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }
}

