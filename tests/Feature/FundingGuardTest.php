<?php

use App\Enums\AccountType;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Support\Ledger\FundingGuard;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

it('allows a deposit into an active user wallet', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    expect(fn () => app(FundingGuard::class)->assertCanDeposit($wallet, $moneyIn, 100_00))
        ->not->toThrow(InvalidTransferException::class);
});

it('allows a withdrawal from an active user wallet', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    expect(fn () => app(FundingGuard::class)->assertCanWithdraw($wallet, $moneyIn, 100_00))
        ->not->toThrow(InvalidTransferException::class);
});

it('rejects non-positive amounts', function (int $amount) {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    expect(fn () => app(FundingGuard::class)->assertCanDeposit($wallet, $moneyIn, $amount))
        ->toThrow(InvalidTransferException::class, 'Amount must be a positive integer in minor units.');
})->with([
    'zero' => 0,
    'negative' => -1,
]);

it('rejects frozen wallets', function () {
    $wallet = Account::factory()->frozen()->create();
    $moneyIn = Account::moneyIn();

    expect(fn () => app(FundingGuard::class)->assertCanDeposit($wallet, $moneyIn, 100_00))
        ->toThrow(InvalidTransferException::class, 'Wallet account is not active.');
});

it('rejects closed wallets', function () {
    $wallet = Account::factory()->closed()->create();
    $moneyIn = Account::moneyIn();

    expect(fn () => app(FundingGuard::class)->assertCanWithdraw($wallet, $moneyIn, 100_00))
        ->toThrow(InvalidTransferException::class, 'Wallet account is not active.');
});

it('rejects using a user account as the funding boundary', function () {
    $wallet = Account::factory()->create();
    $notMoneyIn = Account::factory()->create();

    expect(fn () => app(FundingGuard::class)->assertCanDeposit($wallet, $notMoneyIn, 100_00))
        ->toThrow(InvalidTransferException::class, 'Funding boundary must be a system inbound account.');
});

it('rejects using money_in as the user wallet', function () {
    $moneyIn = Account::moneyIn();
    $otherBoundary = Account::factory()->systemInbound()->create([
        'slug' => 'system_inbound_other',
    ]);

    expect($moneyIn->type)->toBe(AccountType::SystemInbound);

    expect(fn () => app(FundingGuard::class)->assertCanDeposit($moneyIn, $otherBoundary, 100_00))
        ->toThrow(InvalidTransferException::class, 'Wallet must be a user account.');
});

it('rejects currency mismatches', function () {
    $wallet = Account::factory()->currency('USD')->create();
    $moneyIn = Account::moneyIn();

    expect(fn () => app(FundingGuard::class)->assertCanDeposit($wallet, $moneyIn, 100_00))
        ->toThrow(InvalidTransferException::class, 'Accounts must share the same currency.');
});
