<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/v1/wallet/deposit',
    operationId: 'deposit',
    description: 'Credits the authenticated user\'s primary wallet by debiting the local money_in system boundary. No real bank call.',
    summary: 'Deposit funds',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/FundingRequest'),
    ),
    tags: ['Wallet'],
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
            description: 'Deposit posted',
            content: new OA\JsonContent(ref: '#/components/schemas/FundingResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 419, description: 'CSRF token mismatch'),
        new OA\Response(
            response: 422,
            description: 'Validation error, missing primary wallet, or domain rejection',
            content: new OA\JsonContent(ref: '#/components/schemas/MessageOrValidationError'),
        ),
    ],
)]
#[OA\Post(
    path: '/api/v1/wallet/withdraw',
    operationId: 'withdraw',
    description: 'Debits the authenticated user\'s primary wallet by crediting the local money_in system boundary. No real bank call. Fails with insufficient balance when funds are too low.',
    summary: 'Withdraw funds',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/FundingRequest'),
    ),
    tags: ['Wallet'],
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
            description: 'Withdrawal posted',
            content: new OA\JsonContent(ref: '#/components/schemas/FundingResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 419, description: 'CSRF token mismatch'),
        new OA\Response(
            response: 422,
            description: 'Validation error, insufficient balance, missing primary wallet, or domain rejection',
            content: new OA\JsonContent(ref: '#/components/schemas/MessageOrValidationError'),
        ),
    ],
)]
#[OA\Post(
    path: '/api/v1/wallet/transfer',
    operationId: 'transfer',
    description: 'Moves funds from the authenticated user\'s primary wallet to another user wallet identified by account_number. The sender is always the caller\'s own primary wallet.',
    summary: 'Transfer funds',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/TransferRequest'),
    ),
    tags: ['Wallet'],
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
            description: 'Transfer posted',
            content: new OA\JsonContent(ref: '#/components/schemas/FundingResponse'),
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Recipient account number not found'),
        new OA\Response(response: 419, description: 'CSRF token mismatch'),
        new OA\Response(
            response: 422,
            description: 'Validation error, insufficient balance, self-transfer, missing primary wallet, or domain rejection',
            content: new OA\JsonContent(ref: '#/components/schemas/MessageOrValidationError'),
        ),
    ],
)]
class WalletEndpoints {}
