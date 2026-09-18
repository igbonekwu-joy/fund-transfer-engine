<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/accounts/{account}/balance',
    operationId: 'getAccountBalance',
    description: 'Returns the current ledger balance in minor units for an owned user wallet. Other users\' accounts and system accounts return 404.',
    summary: 'Get account balance',
    security: [['sanctum' => []]],
    tags: ['Accounts'],
    parameters: [
        new OA\Parameter(
            name: 'account',
            description: 'Account UUID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', format: 'uuid'),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Current balance',
            content: new OA\JsonContent(ref: '#/components/schemas/AccountBalanceResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(
            response: 404,
            description: 'Account not found or not owned by the caller',
            content: new OA\JsonContent(ref: '#/components/schemas/MessageOrValidationError'),
        ),
    ],
)]
class AccountEndpoints {}
