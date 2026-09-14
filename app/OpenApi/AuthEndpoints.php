<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/v1/auth/register',
    operationId: 'register',
    description: 'Creates a user and sets access_token, refresh_token, and XSRF-TOKEN cookies. CSRF is not required; Origin must be allowed.',
    summary: 'Register',
    security: [],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest'),
    ),
    tags: ['Auth'],
    responses: [
        new OA\Response(
            response: 201,
            description: 'Registered',
            content: new OA\JsonContent(ref: '#/components/schemas/AuthUserResponse'),
        ),
        new OA\Response(response: 419, description: 'Invalid request origin'),
        new OA\Response(
            response: 422,
            description: 'Validation error',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
        ),
    ],
)]
#[OA\Post(
    path: '/api/v1/auth/login',
    operationId: 'login',
    description: 'Authenticates a user and sets access_token, refresh_token, and XSRF-TOKEN cookies. CSRF is not required; Origin must be allowed. Throttled.',
    summary: 'Log in',
    security: [],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'),
    ),
    tags: ['Auth'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Logged in',
            content: new OA\JsonContent(ref: '#/components/schemas/AuthUserResponse'),
        ),
        new OA\Response(response: 401, description: 'Invalid credentials'),
        new OA\Response(response: 419, description: 'Invalid request origin'),
        new OA\Response(
            response: 422,
            description: 'Validation error',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
        ),
        new OA\Response(response: 429, description: 'Too many attempts'),
    ],
)]
#[OA\Post(
    path: '/api/v1/auth/refresh',
    operationId: 'refresh',
    description: 'Rotates tokens using the refresh_token cookie. Requires X-XSRF-TOKEN matching the XSRF-TOKEN cookie.',
    summary: 'Refresh tokens',
    security: [],
    tags: ['Auth'],
    parameters: [
        new OA\Parameter(
            name: 'X-XSRF-TOKEN',
            description: 'Must match the XSRF-TOKEN cookie',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string'),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Tokens refreshed',
            content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
        ),
        new OA\Response(response: 401, description: 'Missing or invalid refresh token'),
        new OA\Response(response: 419, description: 'CSRF token mismatch'),
    ],
)]
#[OA\Post(
    path: '/api/v1/auth/logout',
    operationId: 'logout',
    description: 'Revokes the current session tokens and clears auth cookies.',
    summary: 'Log out',
    security: [['sanctum' => []]],
    tags: ['Auth'],
    parameters: [
        new OA\Parameter(
            name: 'X-XSRF-TOKEN',
            description: 'Must match the XSRF-TOKEN cookie',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string'),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Logged out',
            content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 419, description: 'CSRF token mismatch'),
    ],
)]
#[OA\Get(
    path: '/api/v1/auth/user',
    operationId: 'currentUser',
    summary: 'Current user',
    security: [['sanctum' => []]],
    tags: ['Auth'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Authenticated user',
            content: new OA\JsonContent(ref: '#/components/schemas/CurrentUserResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ],
)]
class AuthEndpoints {}
