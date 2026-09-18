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
#[OA\Get(
    path: '/api/v1/accounts/{account}/transactions',
    operationId: 'getAccountTransactions',
    description: 'Lists ledger movements that touch an owned user wallet, newest first. Each item is the caller\'s leg of a balanced transaction (amount + direction for this account).',
    summary: 'List account transactions',
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
        new OA\Parameter(
            name: 'per_page',
            description: 'Page size (1–100, default 25)',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 25),
        ),
        new OA\Parameter(
            name: 'page',
            description: 'Page number',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Paginated transaction legs for this account',
            content: new OA\JsonContent(ref: '#/components/schemas/AccountTransactionsResponse'),
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
