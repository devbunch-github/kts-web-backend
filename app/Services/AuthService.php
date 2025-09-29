<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
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

        return response()->json([
            'ok'      => true,
            'user_id' => $user->id,
            'name'    => $user->name,
            // 'token' => $token,
        ]);
    }
}
