<?php

use App\Models\Account;
use App\Models\User;
use App\Services\Ledger\FundingService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

it('requires authentication to transfer', function () {
    $this->postJson('/api/v1/wallet/transfer', [
        'account_number' => '0123456789',
        'amount' => 100_00,
    ])->assertUnauthorized();
});

it('transfers from the authenticated users primary wallet to a recipient account number', function () {
    $senderUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $sender = Account::factory()->for($senderUser)->create();
    $recipient = Account::factory()->for($recipientUser)->create();

    app(FundingService::class)->deposit($sender, 10_000_00);

    $response = postWallet($this, $senderUser, '/api/v1/wallet/transfer', [
        'account_number' => $recipient->account_number,
        'amount' => 2_500_00,
        'narration' => 'Lunch',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'transaction' => [
                'id',
                'type',
                'status',
                'metadata',
                'amount',
                'currency',
                'created_at',
            ],
            'balance',
        ])
        ->assertJsonPath('message', 'Transfer successful.')
        ->assertJsonPath('transaction.type', 'transfer')
        ->assertJsonPath('transaction.status', 'posted')
        ->assertJsonPath('transaction.amount', 2_500_00)
        ->assertJsonPath('transaction.currency', 'NGN')
        ->assertJsonPath('transaction.metadata.narration', 'Lunch')
        ->assertJsonPath('balance', 7_500_00);

    expect($sender->fresh()->balanceInMinorUnits())->toBe(7_500_00)
        ->and($recipient->fresh()->balanceInMinorUnits())->toBe(2_500_00);
});

it('rejects transfers when the sender has insufficient balance', function () {
    $senderUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $sender = Account::factory()->for($senderUser)->create();
    $recipient = Account::factory()->for($recipientUser)->create();

    app(FundingService::class)->deposit($sender, 1_000_00);

    $response = postWallet($this, $senderUser, '/api/v1/wallet/transfer', [
        'account_number' => $recipient->account_number,
        'amount' => 2_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Insufficient balance.');

    expect($sender->fresh()->balanceInMinorUnits())->toBe(1_000_00)
        ->and($recipient->fresh()->balanceInMinorUnits())->toBe(0);
});

it('rejects transfers to an unknown account number', function () {
    $senderUser = User::factory()->create();
    $sender = Account::factory()->for($senderUser)->create();

    app(FundingService::class)->deposit($sender, 5_000_00);

    $response = postWallet($this, $senderUser, '/api/v1/wallet/transfer', [
        'account_number' => '9999999999',
        'amount' => 1_000_00,
    ]);

    $response->assertNotFound();
});

it('rejects self-transfers to the same account number', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    app(FundingService::class)->deposit($wallet, 5_000_00);

    $response = postWallet($this, $user, '/api/v1/wallet/transfer', [
        'account_number' => $wallet->account_number,
        'amount' => 1_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Cannot transfer to the same account.');

    expect($wallet->fresh()->balanceInMinorUnits())->toBe(5_000_00);
});

it('rejects transfers to a frozen recipient', function () {
    $senderUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $sender = Account::factory()->for($senderUser)->create();
    $recipient = Account::factory()->for($recipientUser)->frozen()->create();

    app(FundingService::class)->deposit($sender, 5_000_00);

    $response = postWallet($this, $senderUser, '/api/v1/wallet/transfer', [
        'account_number' => $recipient->account_number,
        'amount' => 1_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Recipient account is not active.');

    expect($sender->fresh()->balanceInMinorUnits())->toBe(5_000_00)
        ->and($recipient->fresh()->balanceInMinorUnits())->toBe(0);
});

it('validates transfer payload', function (array $payload, array $errors) {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();

    $response = postWallet($this, $user, '/api/v1/wallet/transfer', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors($errors);
})->with([
    'short account number and zero amount' => [
        ['account_number' => '123', 'amount' => 0],
        ['account_number', 'amount'],
    ],
    'non-digit account number' => [
        ['account_number' => 'abcdefghij', 'amount' => 100_00],
        ['account_number'],
    ],
    'missing account number' => [
        ['amount' => 100_00],
        ['account_number'],
    ],
    'decimal amount' => [
        ['account_number' => '0123456789', 'amount' => '50.25'],
        ['amount'],
    ],
]);

it('rejects client-supplied account ids on transfer', function (string $field) {
    $user = User::factory()->create();
    $sender = Account::factory()->for($user)->create();
    $recipient = Account::factory()->create();

    $response = postWallet($this, $user, '/api/v1/wallet/transfer', [
        'account_number' => $recipient->account_number,
        'amount' => 100_00,
        $field => $sender->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'account_id',
    'from_account_id',
    'to_account_id',
]);

it('rejects transfers when the sender has no primary wallet', function () {
    $user = User::factory()->create();
    $recipient = Account::factory()->create();

    $response = postWallet($this, $user, '/api/v1/wallet/transfer', [
        'account_number' => $recipient->account_number,
        'amount' => 1_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'User does not have a primary wallet.');
});
