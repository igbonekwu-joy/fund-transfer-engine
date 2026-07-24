<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            ->cookie('access_token', $result['access_token'], 15, '/', null, app()->isProduction(), true, false, 'Strict')
            ->cookie('refresh_token', $result['refresh_token'], 60 * 24 * 7, '/', null, app()->isProduction(), true, false, 'Strict');
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token');

        $result = $this->auth->refresh(is_string($refreshToken) ? $refreshToken : null);

        return response()->json(['message' => 'Token refreshed.'])
            ->cookie('access_token', $result['access_token'], 15, '/', null, true, true, false, 'Strict');
    }
}
