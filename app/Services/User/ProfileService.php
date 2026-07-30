<?php

namespace App\Services\User;

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\UnauthenticatedException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileService
{
    public function update(User $user, Request $request): array
    {
        $user->update([
            'name' => $request->fullName,
            'phone' => $request->mobile,
            'gender' => $request->gender ?? 'Male',
            'address' => $request->address,
            'dob' => $request->dob
        ]);

        if(! $user->account_number) {
            $accountNumber = $this->generateAccountNumber();
            $user->update([
                'account_number' => $accountNumber
            ]);
        }

        return [
            'user' => $user->toApiArray()
        ];
    }

    private function generateAccountNumber(): string
    {
        $accountNumber = (string) random_int(1, 9);

        for ($i = 0; $i < 9; $i++) {
            $accountNumber .= random_int(0, 9);
        }

        return $accountNumber;
    }
}
