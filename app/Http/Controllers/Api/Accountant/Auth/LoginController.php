<?php
namespace App\Http\Controllers\Api\Accountant\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    protected $auth;

    public function __construct()
    {
        // If you have an AuthService handling role hierarchy logic
        $this->auth = app('App\Services\AuthService');
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
                'message' => 'Invalid credentials',
            ], 401);
        }

        // 🔹 Create token (Laravel Sanctum)
        $token = $user->createToken('auth-token')->plainTextToken;

        // 🔹 Get user roles via Spatie
        $roles = $user->getRoleNames(); // returns collection

        // 🔹 Determine primary role (your AuthService handles hierarchy)
        $primaryRole = $this->auth->getPrimaryRole($roles);

        // 🔹 Define redirect URLs per role
        $redirects = [
            'super_admin' => '/admin/dashboard',
            'accountant'  => '/accountant/dashboard',
            'business'    => '/business/dashboard',
        ];

        $redirectUrl = $redirects[$primaryRole] ?? '/dashboard';

        // 🔹 Collect permissions if you use them on frontend
        $permissions = $user->getAllPermissions()->pluck('name');

        // 🔹 Build response
        return response()->json([
            'message' => 'Logged in successfully',
            'token' => $token,
            'user' => $user,
            'user_data' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $primaryRole,
                'roles'       => $roles,
                'permissions' => $permissions,
            ],
            'redirect_url' => $redirectUrl,
        ]);
    }
}
