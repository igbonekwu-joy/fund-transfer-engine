<?php

namespace App\Services\Auth;

use App\Models\User;

class AuthService
{
    public function createUser(string $name, string $email, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        return $user;
    }
}
