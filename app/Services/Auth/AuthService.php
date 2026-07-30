<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\UnauthenticatedException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * @return array{user: array<string, string>, access_token: string, refresh_token: string}
     */
    public function register(string $name, string $email, string $password): array
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $csrfToken = Str::random(40);

        return [
            'user' => $user->toApiArray(),
            'access_token' => $user->createToken('fundTransferAuthToken', ['*'], now()->addMinutes(15))->plainTextToken,
            'refresh_token' => $user->createToken('fundTransferRefreshToken', ['refresh'], now()->addDays(7))->plainTextToken,
            'csrf_token' => $csrfToken,
        ];
    }

    /**
     * @return array{user: User, access_token: string, refresh_token: string}
     */
    public function refresh(?string $refreshToken): array
    {
        if ($refreshToken === null || $refreshToken === '') {
            throw new UnauthenticatedException('Refresh token not found.');
        }

        if (! str_contains($refreshToken, '|')) {
            throw new UnauthenticatedException('Invalid refresh token.');
        }

        [$id, $plainToken] = explode('|', $refreshToken, 2);

        $tokenModel = PersonalAccessToken::find($id);

        if (! $tokenModel || ! hash_equals($tokenModel->token, hash('sha256', $plainToken))) {
            throw new UnauthenticatedException('Invalid refresh token.');
        }

        if (! $tokenModel->can('refresh')) {
            throw new UnauthenticatedException('Invalid refresh token.');
        }

        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            // Committed immediately, no open transaction to roll it back
            $tokenModel->delete();

            throw new UnauthenticatedException('Refresh token has expired.');
        }

        $csrfToken = Str::random(40);

        return DB::transaction(function () use ($id, $csrfToken) {
            $tokenModel = PersonalAccessToken::where('id', $id)
                ->lockForUpdate()
                ->first();

            // Re-check under lock in case it was deleted/expired between the
            // check above and acquiring this lock (race condition guard)
            if (! $tokenModel) {
                throw new UnauthenticatedException('Refresh token has already been used.');
            }

            $user = $tokenModel->tokenable;

            if (! $user instanceof User) {
                throw new UnauthenticatedException('Invalid refresh token.');
            }

            $deleted = PersonalAccessToken::where('id', $tokenModel->id)->delete();

            if ($deleted !== 1) {
                throw new UnauthenticatedException('Refresh token has already been used.');
            }

            $user->tokens()->where('name', 'fundTransferAuthToken')->delete();

            return [
                'user' => $user,
                'access_token' => $user->createToken('fundTransferAuthToken', ['*'], now()->addMinutes(15))->plainTextToken,
                'refresh_token' => $user->createToken('fundTransferRefreshToken', ['refresh'], now()->addDays(7))->plainTextToken,
                'csrf_token' => $csrfToken,
            ];
        });
    }

    /**
     * @return array{user: array<string, string>, access_token: string, refresh_token: string}
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', '=', $email, '')->first();

        if (! $user || ! password_verify($password, $user->password)) {
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        $csrfToken = Str::random(40);

        return [
            'user' => $user->toApiArray(),
            'access_token' => $user->createToken('fundTransferAuthToken', ['*'], now()->addMinutes(15))->plainTextToken,
            'refresh_token' => $user->createToken('fundTransferRefreshToken', ['refresh'], now()->addDays(7))->plainTextToken,
            'csrf_token' => $csrfToken,
        ];
    }

    public function logout(Request $request): void
    {
        $user = $request->user();

        /** @var PersonalAccessToken $currentToken */
        $currentToken = $request->user()->currentAccessToken();

        DB::transaction(function () use ($user, $currentToken) {
            $currentToken->delete();

            $user->tokens()
                ->where('name', 'fundTransferRefreshToken')
                ->delete();
        });
    }
}
