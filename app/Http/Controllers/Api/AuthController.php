<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        $r->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $r->email)->first();

        if (!$user || !Hash::check($r->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create token - this will now work with HasApiTokens trait
        $token = $user->createToken('auth-token')->plainTextToken;

        // Get user roles using Spatie
        $roles = $user->getRoleNames(); // Returns collection of role names

        // Determine primary role for redirect (using hierarchy)
        $primaryRole = $this->auth->getPrimaryRole($roles);
        
        // Define redirect URLs per role
        $redirects = [
            'super_admin' => '/admin/dashboard',
            'accountant'  => '/accountant/dashboard',
            'business'    => '/business/dashboard',
        ];

        $redirectUrl = $redirects[$primaryRole] ?? '/dashboard';

        // Get user permissions (optional - if you want to send them to frontend)
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'message' => 'Logged in successfully',
            'token' => $token,
            'user' => $user,
            'user_data'     => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $primaryRole,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
            'redirect_url' => $redirectUrl,
        ]);
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
