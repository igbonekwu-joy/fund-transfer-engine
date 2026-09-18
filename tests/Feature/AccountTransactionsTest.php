<?php

use App\Models\Account;
use App\Models\User;
use App\Services\Ledger\FundingService;
use App\Services\Ledger\TransferService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

it('requires authentication to list transactions', function () {
    $wallet = Account::factory()->create();

    $this->getJson("/api/v1/accounts/{$wallet->id}/transactions")
        ->assertUnauthorized();
});

it('returns an empty list when the wallet has no ledger activity', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/accounts/{$wallet->id}/transactions");

    $response->assertOk()
        ->assertExactJson([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'per_page' => 25,
                'total' => 0,
                'last_page' => 1,
            ],
        ]);
});

it('lists deposit and transfer legs for an owned wallet newest first', function () {
    $senderUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $sender = Account::factory()->for($senderUser)->create();
    $recipient = Account::factory()->for($recipientUser)->create();

    app(FundingService::class)->deposit($sender, 10_000_00, ['narration' => 'Top up']);
    app(TransferService::class)->transfer($sender, $recipient, 2_500_00, ['narration' => 'Lunch']);

    $response = $this->actingAs($senderUser, 'sanctum')
        ->getJson("/api/v1/accounts/{$sender->id}/transactions");

    $response->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.type', 'transfer')
        ->assertJsonPath('data.0.direction', 'debit')
        ->assertJsonPath('data.0.amount', 2_500_00)
        ->assertJsonPath('data.0.narration', 'Lunch')
        ->assertJsonPath('data.1.type', 'deposit')
        ->assertJsonPath('data.1.direction', 'credit')
        ->assertJsonPath('data.1.amount', 10_000_00)
        ->assertJsonPath('data.1.narration', 'Top up')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'type',
                    'status',
                    'amount',
                    'currency',
                    'direction',
                    'narration',
                    'created_at',
                ],
            ],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
});

it('shows an incoming transfer as a credit on the recipient wallet', function () {
    $senderUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $sender = Account::factory()->for($senderUser)->create();
    $recipient = Account::factory()->for($recipientUser)->create();

    app(FundingService::class)->deposit($sender, 5_000_00);
    app(TransferService::class)->transfer($sender, $recipient, 1_500_00, ['narration' => 'Gift']);

    $response = $this->actingAs($recipientUser, 'sanctum')
        ->getJson("/api/v1/accounts/{$recipient->id}/transactions");

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.type', 'transfer')
        ->assertJsonPath('data.0.direction', 'credit')
        ->assertJsonPath('data.0.amount', 1_500_00)
        ->assertJsonPath('data.0.narration', 'Gift');
});

it('paginates transaction history', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();
    $funding = app(FundingService::class);

    $funding->deposit($wallet, 100_00);
    $funding->deposit($wallet, 200_00);
    $funding->deposit($wallet, 300_00);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/accounts/{$wallet->id}/transactions?per_page=2&page=1");

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.amount', 300_00)
        ->assertJsonPath('data.1.amount', 200_00);
});

it('returns 404 when listing another users transactions', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $wallet = Account::factory()->for($owner)->create();

    app(FundingService::class)->deposit($wallet, 1_000_00);

    $this->actingAs($other, 'sanctum')
        ->getJson("/api/v1/accounts/{$wallet->id}/transactions")
        ->assertNotFound()
        ->assertJsonPath('message', 'Account not found.');
});
