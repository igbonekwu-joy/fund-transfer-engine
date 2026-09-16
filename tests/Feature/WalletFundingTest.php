<?php

use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\User;
use App\Services\Ledger\FundingService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

/**
 * @return array<string, string>
 */
function walletRequestServer(string $csrf): array
{
    return [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_ORIGIN' => 'http://localhost',
        'HTTP_X_XSRF_TOKEN' => $csrf,
    ];
}

/**
 * @param  array<string, mixed>  $data
 */
function walletJsonBody(array $data): string
{
    return json_encode($data, JSON_THROW_ON_ERROR);
}

/**
 * @param  array<string, mixed>  $payload
 */
function postWallet(mixed $testCase, User $user, string $uri, array $payload): mixed
{
    $csrf = Str::random(40);

    return $testCase->actingAs($user, 'sanctum')
        ->call(
            'POST',
            $uri,
            [],
            ['XSRF-TOKEN' => $csrf],
            [],
            walletRequestServer($csrf),
            walletJsonBody($payload),
        );
}

it('requires authentication to deposit', function () {
    $this->postJson('/api/v1/wallet/deposit', ['amount' => 100_00])
        ->assertUnauthorized();
});

it('deposits into the authenticated users primary wallet', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    $response = postWallet($this, $user, '/api/v1/wallet/deposit', [
        'amount' => 2_500_00,
        'narration' => 'Top up',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Deposit successful.')
        ->assertJsonPath('transaction.type', 'deposit')
        ->assertJsonPath('transaction.amount', 2_500_00)
        ->assertJsonPath('transaction.metadata.narration', 'Top up')
        ->assertJsonPath('balance', 2_500_00);

    expect($wallet->fresh()->balanceInMinorUnits())->toBe(2_500_00)
        ->and(Account::moneyIn()->balanceInMinorUnits())->toBe(2_500_00);
});

it('withdraws from the authenticated users primary wallet', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    app(FundingService::class)->deposit($wallet, 10_000_00);

    $response = postWallet($this, $user, '/api/v1/wallet/withdraw', [
        'amount' => 2_500_00,
        'narration' => 'Cash out',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Withdrawal successful.')
        ->assertJsonPath('transaction.type', 'withdrawal')
        ->assertJsonPath('transaction.amount', 2_500_00)
        ->assertJsonPath('balance', 7_500_00);

    expect($wallet->fresh()->balanceInMinorUnits())->toBe(7_500_00);
});

it('rejects withdraw when balance is insufficient', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    app(FundingService::class)->deposit($wallet, 1_000_00);

    $response = postWallet($this, $user, '/api/v1/wallet/withdraw', [
        'amount' => 2_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Insufficient balance.');

    expect($wallet->fresh()->balanceInMinorUnits())->toBe(1_000_00);
});

it('rejects funding when the user has no primary wallet', function () {
    $user = User::factory()->create();

    $response = postWallet($this, $user, '/api/v1/wallet/deposit', [
        'amount' => 1_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'User does not have a primary wallet.');
});

it('validates amount on deposit', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();

    $response = postWallet($this, $user, '/api/v1/wallet/deposit', [
        'amount' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('amount');
});

it('validates negative amounts on withdraw', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create();

    $response = postWallet($this, $user, '/api/v1/wallet/withdraw', [
        'amount' => -100_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('amount');
});

it('rejects deposits into a frozen primary wallet', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->frozen()->create();

    $response = postWallet($this, $user, '/api/v1/wallet/deposit', [
        'amount' => 1_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Wallet account is not active.');
});

it('rejects withdrawals from a closed primary wallet', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    app(FundingService::class)->deposit($wallet, 5_000_00);
    $wallet->update(['status' => AccountStatus::Closed]);

    $response = postWallet($this, $user, '/api/v1/wallet/withdraw', [
        'amount' => 1_000_00,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Wallet account is not active.');

    expect($wallet->fresh()->balanceInMinorUnits())->toBe(5_000_00);
});

it('requires authentication to withdraw', function () {
    $this->postJson('/api/v1/wallet/withdraw', ['amount' => 100_00])
        ->assertUnauthorized();
});
