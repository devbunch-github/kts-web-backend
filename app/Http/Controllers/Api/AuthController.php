<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

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
            'email'   => 'required|email|unique:users,email',
            'name'    => 'required|string|max:191',
            'country' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'country'  => $data['country'] ?? null,
                'password' => bcrypt(Str::random(12)),
                'status'   => 'pending',
            ]);

            $role = Role::firstOrCreate(['name' => 'business_admin', 'guard_name' => 'web']);
            $user->assignRole($role);

            // Keep your existing service logic
            $bundle = app(\App\Services\Auth\PreRegisterOrchestrator::class)
                ->afterPreRegister($user);

            DB::commit();

            return response()->json([
                'id'         => $user->id,
                'email'      => $user->email,
                'account_id' => $bundle['account']->Id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
        }
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

    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = User::where('email', $request->email)->exists();

        if ($exists) {
            return response()->json([
                'exists' => true,
                'message' => 'This email is already registered. Please sign in instead.'
            ], 409);
        }

        return response()->json([
            'exists' => false,
            'message' => 'Email is available.'
        ]);
    }

}

