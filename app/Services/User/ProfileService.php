<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileService
{
    /**
     * @return array{user: array<string, string>}
     */
    public function update(User $user, Request $request): array
    {
        $user->update([
            'name' => $request->fullName,
            'phone' => $request->mobile,
            'gender' => $request->gender ?? 'Male',
            'address' => $request->address,
            'dob' => $request->dob,
        ]);

        if (! $user->account_number) {
            $accountNumber = $this->generateAccountNumber();
            $user->update([
                'account_number' => $accountNumber,
            ]);
        }

        return [
            'user' => $user->toApiArray(),
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
