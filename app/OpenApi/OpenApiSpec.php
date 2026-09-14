<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Fund Transfer Engine API',
    description: <<<'MD'
JSON API for authentication, profile, and KYC.

Authentication uses Laravel Sanctum. Login, register, and refresh set httpOnly `access_token` and `refresh_token` cookies plus a non-httpOnly `XSRF-TOKEN` cookie. Protected routes also accept an `Authorization: Bearer {token}` header.

Mutating requests (except login and register) require an `X-XSRF-TOKEN` header that matches the `XSRF-TOKEN` cookie. Login and register skip CSRF but require an `Origin` header listed in `CORS_ALLOWED_ORIGINS`.
MD
)]
#[OA\Server(url: '/', description: 'Current host')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Sanctum personal access token. Also sent automatically via the access_token cookie on same-origin requests.',
    bearerFormat: 'Sanctum',
    scheme: 'bearer',
)]
#[OA\Tag(name: 'Auth', description: 'Registration, login, token refresh, and session')]
#[OA\Tag(name: 'User', description: 'Authenticated profile and KYC')]
class OpenApiSpec {}
