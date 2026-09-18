<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['name', 'email', 'initials'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Joy Adeyemi'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joy@example.com'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '08012345678'),
        new OA\Property(property: 'dob', type: 'string', nullable: true, example: '1990-01-15'),
        new OA\Property(property: 'gender', type: 'string', nullable: true, example: 'female'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: '12 Admiralty Way, Lagos'),
        new OA\Property(property: 'account_number', type: 'string', nullable: true, example: '0123456789'),
        new OA\Property(property: 'initials', type: 'string', example: 'JA'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'MessageResponse',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'AuthUserResponse',
    required: ['message', 'user'],
    properties: [
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'CurrentUserResponse',
    required: ['user'],
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['name', 'email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ProfileRequest',
    required: ['fullName', 'mobile', 'address', 'dob'],
    properties: [
        new OA\Property(property: 'fullName', type: 'string', maxLength: 255),
        new OA\Property(property: 'gender', type: 'string', nullable: true, maxLength: 255),
        new OA\Property(property: 'mobile', type: 'string', maxLength: 255),
        new OA\Property(property: 'address', type: 'string', maxLength: 255),
        new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1990-01-15'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Kyc',
    properties: [
        new OA\Property(property: 'tier', type: 'string', example: 'tier1'),
        new OA\Property(property: 'status', type: 'string', example: 'verified'),
        new OA\Property(property: 'provider', type: 'string', nullable: true, example: 'nin'),
        new OA\Property(property: 'bvn_verified', type: 'boolean'),
        new OA\Property(property: 'nin_verified', type: 'boolean'),
        new OA\Property(property: 'verified_at', type: 'string', nullable: true),
        new OA\Property(property: 'rejection_reason', type: 'string', nullable: true),
        new OA\Property(property: 'provider_response', type: 'object', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'KycResponse',
    required: ['kyc'],
    properties: [
        new OA\Property(property: 'kyc', ref: '#/components/schemas/Kyc', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'KycSubmitResponse',
    required: ['message', 'kyc'],
    properties: [
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'kyc', ref: '#/components/schemas/Kyc'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'KycRequest',
    required: ['tier'],
    properties: [
        new OA\Property(property: 'tier', type: 'string', enum: ['tier1', 'tier2', 'tier3']),
        new OA\Property(property: 'provider', type: 'string', enum: ['nin', 'bvn'], description: 'Required for tier1'),
        new OA\Property(property: 'id_number', type: 'string', pattern: '^\d{11}$', description: 'Required for tier1'),
        new OA\Property(property: 'supporting_document_type', type: 'string', enum: ['passport', 'utility_bill', 'bank_statement'], description: 'Required for tier2'),
        new OA\Property(property: 'supporting_document_value', type: 'string', maxLength: 255, description: 'Required for tier2'),
        new OA\Property(property: 'tier3_consent', type: 'boolean', description: 'Required for tier3'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['email' => ['The email has already been taken.']],
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'FundingRequest',
    required: ['amount'],
    properties: [
        new OA\Property(
            property: 'amount',
            description: 'Amount in minor units (kobo)',
            type: 'integer',
            minimum: 1,
            example: 250000,
        ),
        new OA\Property(
            property: 'narration',
            description: 'Optional note stored on the ledger transaction metadata',
            type: 'string',
            maxLength: 255,
            nullable: true,
            example: 'Top up',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'TransferRequest',
    required: ['account_number', 'amount'],
    properties: [
        new OA\Property(
            property: 'account_number',
            description: 'Recipient 10-digit account number',
            type: 'string',
            pattern: '^\d{10}$',
            example: '0123456789',
        ),
        new OA\Property(
            property: 'amount',
            description: 'Amount in minor units (kobo)',
            type: 'integer',
            minimum: 1,
            example: 250000,
        ),
        new OA\Property(
            property: 'narration',
            description: 'Optional note stored on the ledger transaction metadata',
            type: 'string',
            maxLength: 255,
            nullable: true,
            example: 'Lunch',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'LedgerTransaction',
    required: ['id', 'type', 'status', 'amount', 'currency'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'type', type: 'string', enum: ['deposit', 'withdrawal', 'transfer', 'fee'], example: 'deposit'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'posted', 'failed', 'reversed'], example: 'posted'),
        new OA\Property(
            property: 'metadata',
            type: 'object',
            nullable: true,
            example: ['narration' => 'Top up'],
        ),
        new OA\Property(property: 'amount', description: 'Amount in minor units (kobo)', type: 'integer', example: 250000),
        new OA\Property(property: 'currency', type: 'string', example: 'NGN'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'FundingResponse',
    required: ['message', 'transaction', 'balance'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Deposit successful.'),
        new OA\Property(property: 'transaction', ref: '#/components/schemas/LedgerTransaction'),
        new OA\Property(
            property: 'balance',
            description: 'Updated primary wallet balance in minor units (kobo)',
            type: 'integer',
            example: 250000,
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'AccountBalanceResponse',
    required: ['account_id', 'currency', 'balance'],
    properties: [
        new OA\Property(property: 'account_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'currency', type: 'string', example: 'NGN'),
        new OA\Property(
            property: 'balance',
            description: 'Current balance in minor units (kobo)',
            type: 'integer',
            example: 325000,
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'AccountTransactionItem',
    required: ['id', 'type', 'status', 'amount', 'currency', 'direction', 'created_at'],
    properties: [
        new OA\Property(property: 'id', description: 'Ledger transaction UUID', type: 'string', format: 'uuid'),
        new OA\Property(property: 'type', type: 'string', enum: ['deposit', 'withdrawal', 'transfer', 'fee'], example: 'transfer'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'posted', 'failed', 'reversed'], example: 'posted'),
        new OA\Property(property: 'amount', description: 'Leg amount in minor units (kobo)', type: 'integer', example: 250000),
        new OA\Property(property: 'currency', type: 'string', example: 'NGN'),
        new OA\Property(property: 'direction', type: 'string', enum: ['debit', 'credit'], example: 'debit'),
        new OA\Property(property: 'narration', type: 'string', nullable: true, example: 'Lunch'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'AccountTransactionsResponse',
    required: ['data', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/AccountTransactionItem'),
        ),
        new OA\Property(
            property: 'meta',
            required: ['current_page', 'per_page', 'total', 'last_page'],
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'per_page', type: 'integer', example: 25),
                new OA\Property(property: 'total', type: 'integer', example: 2),
                new OA\Property(property: 'last_page', type: 'integer', example: 1),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'MessageOrValidationError',
    description: 'Either a domain message (insufficient balance, missing wallet) or a Laravel validation error payload.',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Insufficient balance.'),
        new OA\Property(
            property: 'errors',
            description: 'Present for FormRequest validation failures',
            type: 'object',
            nullable: true,
            example: ['amount' => ['The amount must be a positive integer in minor units.']],
        ),
    ],
    type: 'object',
)]
class Schemas {}
