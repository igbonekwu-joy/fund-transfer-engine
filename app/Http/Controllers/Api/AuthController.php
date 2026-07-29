<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $result['user'],
        ], 201)
            ->cookie('access_token', $result['access_token'], 15, '/', null, true, true, false, 'None')
            ->cookie('refresh_token', $result['refresh_token'], 60 * 24 * 7, '/api/v1/auth/refresh', null, true, true, false, 'None');
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');

        $result = $this->auth->refresh(is_string($refreshToken) ? $refreshToken : null);

        return response()->json(['message' => 'Token refreshed.'])
            ->cookie('access_token', $result['access_token'], 15, '/', null, true, true, false, 'None')
            ->cookie('refresh_token', $result['refresh_token'], 60 * 24 * 7, '/api/v1/auth/refresh', null, true, true, false, 'None');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->string('email')->toString();
        $password = $request->string('password')->toString();

        $user = $this->auth->login($email, $password);

        return response()->json([
            'message' => 'User logged in successfully.',
            'user' => $user['user'],
        ], 200)
            ->cookie('access_token', $user['access_token'], 15, '/', null, true, true, false, 'None')
            ->cookie('refresh_token', $user['refresh_token'], 60 * 24 * 7, '/api/v1/auth/refresh', null, true, true, false, 'None');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return response()->json(['message' => 'User logged out successfully.']);
    }

    public function currentUser(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->toApiArray(),
        ]);
    }
}
