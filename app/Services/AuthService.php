<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

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

    public function login(string $email)
    {
        $user = User::where('email',$email)->first();
        if (!$user) return response()->json(['ok'=>false,'message'=>'User not found'], 404);
        return response()->json(['ok'=>true,'user_id'=>$user->id,'name'=>$user->name]);
    }
}
