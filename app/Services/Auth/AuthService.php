<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\InvalidRefreshTokenException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * @return array{user: User, access_token: string, refresh_token: string}
     */
    public function register(string $name, string $email, string $password): array
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        return [
            'user' => $user,
            'access_token' => $user->createToken('fundTransferAuthToken', ['*'], now()->addMinutes(15))->plainTextToken,
            'refresh_token' => $user->createToken('fundTransferRefreshToken', ['refresh'], now()->addDays(7))->plainTextToken,
        ];
    }

    /**
     * @return array{user: User, access_token: string, refresh_token: string}
     */
    public function refresh(?string $refreshToken): array
    {
        if ($refreshToken === null || $refreshToken === '') {
            throw new InvalidRefreshTokenException('Refresh token not found.');
        }

        if (! str_contains($refreshToken, '|')) {
            throw new InvalidRefreshTokenException('Invalid refresh token.');
        }

        [$id, $plainToken] = explode('|', $refreshToken, 2);

        return DB::transaction(function () use ($id, $plainToken) {
            $tokenModel = PersonalAccessToken::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $tokenModel || ! hash_equals($tokenModel->token, hash('sha256', $plainToken))) {
                throw new InvalidRefreshTokenException('Invalid refresh token.');
            }

            if (! $tokenModel->can('refresh')) {
                throw new InvalidRefreshTokenException('Invalid refresh token.');
            }

            if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
                $tokenModel->delete();

                throw new InvalidRefreshTokenException('Refresh token has expired.');
            }

            $user = $tokenModel->tokenable;

            if (! $user instanceof User) {
                throw new InvalidRefreshTokenException('Invalid refresh token.');
            }

            $deleted = PersonalAccessToken::where('id', $tokenModel->id)->delete();

            if ($deleted !== 1) {
                throw new InvalidRefreshTokenException('Refresh token has already been used.');
            }

            $user->tokens()->where('name', 'fundTransferAuthToken')->delete();

            return [
                'user' => $user,
                'access_token' => $user->createToken('fundTransferAuthToken', ['*'], now()->addMinutes(15))->plainTextToken,
                'refresh_token' => $user->createToken('fundTransferRefreshToken', ['refresh'], now()->addDays(7))->plainTextToken,
            ];
        });
    }
}
