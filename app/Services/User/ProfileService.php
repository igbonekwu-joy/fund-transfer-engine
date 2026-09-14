<?php

namespace App\Services\User;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProfileService
{
    /**
     * @return array{user: array<string, string|null>}
     */
    public function update(User $user, Request $request): array
    {
        $user->fill([
            'name' => $request->fullName,
            'phone' => $request->mobile,
            'gender' => $request->gender,
            'address' => $request->address,
            'dob' => $request->dob,
        ])->save();

        if ($user->account === null) {
            $this->createPrimaryAccount($user);
        }

        return [
            'user' => $user->load('account')->toApiArray(),
        ];
    }

    private function createPrimaryAccount(User $user): Account
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $accountNumber = $this->buildAccountNumber();

            try {
                return $user->account()->create([
                    'type' => AccountType::User,
                    'status' => AccountStatus::Active,
                    'currency' => 'NGN',
                    'account_number' => $accountNumber,
                    'name' => 'Primary wallet',
                ]);
            } catch (QueryException $e) {
                if ($this->isUniqueConstraintViolation($e) && $attempt < $maxAttempts) {
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Could not generate a unique account number after '.$maxAttempts.' attempts.');
    }

    private function buildAccountNumber(): string
    {
        $accountNumber = (string) random_int(1, 9);

        for ($i = 0; $i < 9; $i++) {
            $accountNumber .= random_int(0, 9);
        }

        return $accountNumber;
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
}
