<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProfileService
{
    /**
     * @return array{user: array<string, string>}
     */
    public function update(User $user, Request $request): array
    {
        $user->fill([
            'name' => $request->fullName,
            'phone' => $request->mobile,
            'gender' => $request->gender,
            'address' => $request->address,
            'dob' => $request->dob,
        ]);

        if (! $user->account_number) {
            $this->generateAccountNumber($user);
        }

        return [
            'user' => $user->toApiArray(),
        ];
    }

    private function generateAccountNumber(User $user): string
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $accountNumber = $this->buildAccountNumber();

            try {
                $user->forceFill(['account_number' => $accountNumber])->save();

                return $accountNumber;
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
        return $e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry');
    }
}
