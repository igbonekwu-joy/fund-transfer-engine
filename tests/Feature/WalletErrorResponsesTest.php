<?php

use App\Models\Account;
use App\Models\User;
use App\Services\Ledger\FundingService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

/**
 * Stage 5 Step 4: money API failures share a stable JSON envelope.
 */
function assertMoneyApiError(TestResponse $response, int $status, string $message, bool $hasValidationErrors = false): void
{
    $response->assertStatus($status)
        ->assertJsonPath('message', $message)
        ->assertJsonMissingPath('exception')
        ->assertJsonMissingPath('file')
        ->assertJsonMissingPath('trace');

    if ($hasValidationErrors) {
        $response->assertJsonStructure(['message', 'errors']);
    } else {
        $response->assertExactJson(['message' => $message]);
    }
}

it('returns 401 with a message-only envelope when unauthenticated', function () {
    $response = $this->postJson('/api/v1/wallet/deposit', ['amount' => 100_00]);

    assertMoneyApiError($response, 401, 'Unauthenticated.');
});

it('returns 419 with a message-only envelope when CSRF is missing', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->call(
            'POST',
            '/api/v1/wallet/deposit',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_ORIGIN' => 'http://localhost',
            ],
            walletJsonBody(['amount' => 100_00]),
        );

    assertMoneyApiError($response, 419, 'CSRF token mismatch.');
});

it('returns 422 validation JSON with message and errors keys', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();

    $response = postWallet($this, $user, '/api/v1/wallet/deposit', [
        'amount' => 0,
    ]);

    assertMoneyApiError(
        $response,
        422,
        'The amount must be a positive integer in minor units.',
        hasValidationErrors: true,
    );
});

it('returns 422 message-only for insufficient funds', function () {
    $senderUser = User::factory()->create();
    $recipientUser = User::factory()->create();
    $sender = Account::factory()->for($senderUser)->create();
    $recipient = Account::factory()->for($recipientUser)->create();

    app(FundingService::class)->deposit($sender, 500_00);

    $response = postWallet($this, $senderUser, '/api/v1/wallet/transfer', [
        'account_number' => $recipient->account_number,
        'amount' => 1_000_00,
    ]);

    assertMoneyApiError($response, 422, 'Insufficient balance.');
});

it('returns 422 message-only for self-transfers', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    app(FundingService::class)->deposit($wallet, 1_000_00);

    $response = postWallet($this, $user, '/api/v1/wallet/transfer', [
        'account_number' => $wallet->account_number,
        'amount' => 100_00,
    ]);

    assertMoneyApiError($response, 422, 'Cannot transfer to the same account.');
});

it('returns 404 message-only for unknown recipients without leaking model class names', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    app(FundingService::class)->deposit($wallet, 1_000_00);

    $response = postWallet($this, $user, '/api/v1/wallet/transfer', [
        'account_number' => '9999999999',
        'amount' => 100_00,
    ]);

    assertMoneyApiError($response, 404, 'Account not found.');

    expect($response->getContent())->not->toContain('App\\Models\\Account');
});

it('returns 422 message-only when the user has no primary wallet', function () {
    $user = User::factory()->create();
    $recipient = Account::factory()->create();

    $response = postWallet($this, $user, '/api/v1/wallet/transfer', [
        'account_number' => $recipient->account_number,
        'amount' => 100_00,
    ]);

    assertMoneyApiError($response, 422, 'User does not have a primary wallet.');
});
