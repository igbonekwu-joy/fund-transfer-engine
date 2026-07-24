<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) { }

    public function register(RegisterRequest $request): JsonResponse
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = $this->auth->createUser($name, $email, $password);
        $accessToken = $user->createToken('fundTransferAuthToken', ['*'], now()->addMinutes(15))->plainTextToken;
        $refreshToken = $user->createToken('fundTransferRefreshToken', ['refresh'], now()->addDays(7))->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
        ], 201)
           ->cookie('access_token', $accessToken, 15, '/', null, true, true, false, 'Strict')
            ->cookie('refresh_token', $refreshToken, 60 * 24 * 7, '/api/v1/refresh', null, true, true, false, 'Strict');

    // cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite)
    }
}
