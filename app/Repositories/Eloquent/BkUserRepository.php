<?php

// app/Repositories/BkUserRepository.php
namespace App\Repositories\Eloquent;

use App\Models\BkUser;

class BkUserRepository
{
    public function create(array $data): BkUser
    {
        return BkUser::create($data);
    }

    public function findByEmail(string $email): ?BkUser
    {
        return BkUser::where('Email',$email)->first();
    }
}
