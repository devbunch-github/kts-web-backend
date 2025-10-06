<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthService
{

    /**
     * Determine the primary role based on hierarchy
     */
    private function getPrimaryRole($roles)
    {
        // Define role hierarchy (highest to lowest priority)
        $hierarchy = [
            'super_admin',
            'accountant',
            'business'
        ];

        foreach ($hierarchy as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }

        return 'business'; // default role
    }

    public function register(array $v)
    {
        $user = User::create([
            'name' => $v['name'],
            'email'=> $v['email'],
            'password' => bcrypt($v['password'] ?? Str::random(12)),
        ]);
        // attach business_name/phone to profile table if you have one
        return response()->json(['ok'=>true,'user_id'=>$user->id]);
    }

    public function login(string $email, string $password, bool $remember = false)
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            // Return 422 like most SPA APIs for bad credentials
            return response()->json(['ok' => false, 'message' => 'Invalid email or password'], 422);
        }

        // If you add Sanctum later, generate token here and return it.
        // $token = $user->createToken('web')->plainTextToken;

        // Get user roles using Spatie
        $roles = $user->getRoleNames(); // Returns collection of role names

        // Determine primary role for redirect (using hierarchy)
        $primaryRole = $this->getPrimaryRole($roles);

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
            'ok'       => true,
            'user'     => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $primaryRole,
                'roles' => $roles, // All roles user has
                'permissions' => $permissions, // All permissions user has
            ],
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Get all user permissions
     */
    public function getUserPermissions(User $user)
    {
        return $user->getAllPermissions()->pluck('name');
    }

    /**
     * Get all user roles
     */
    public function getUserRoles(User $user)
    {
        return $user->getRoleNames();
    }
}