<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/v1/user/profile',
    operationId: 'updateProfile',
    summary: 'Update profile',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ProfileRequest'),
    ),
    tags: ['User'],
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
            description: 'Profile updated',
            content: new OA\JsonContent(ref: '#/components/schemas/AuthUserResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 419, description: 'CSRF token mismatch'),
        new OA\Response(
            response: 422,
            description: 'Validation error',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
        ),
    ],
)]
#[OA\Get(
    path: '/api/v1/user/kyc',
    operationId: 'showKyc',
    summary: 'Get KYC status',
    security: [['sanctum' => []]],
    tags: ['User'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Current KYC record, or null when none exists',
            content: new OA\JsonContent(ref: '#/components/schemas/KycResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ],
)]
#[OA\Post(
    path: '/api/v1/user/kyc',
    operationId: 'submitKyc',
    summary: 'Submit KYC',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/KycRequest'),
    ),
    tags: ['User'],
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
            description: 'KYC submitted',
            content: new OA\JsonContent(ref: '#/components/schemas/KycSubmitResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 419, description: 'CSRF token mismatch'),
        new OA\Response(
            response: 422,
            description: 'Validation error',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
        ),
    ],
)]
class UserEndpoints {}
