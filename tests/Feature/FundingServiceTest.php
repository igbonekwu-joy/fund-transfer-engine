<?php

use App\Enums\AccountStatus;
use App\Enums\TransactionType;
use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Services\Ledger\FundingService;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

it('deposits funds into a user wallet via money_in', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    $transaction = app(FundingService::class)->deposit(
        $wallet,
        2_500_00,
        ['narration' => 'Test deposit'],
    );

    expect($transaction->type)->toBe(TransactionType::Deposit)
        ->and($transaction->isBalanced())->toBeTrue()
        ->and($transaction->ledgerEntries)->toHaveCount(2)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(2_500_00)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(2_500_00)
        ->and($transaction->metadata)->toBe(['narration' => 'Test deposit']);
});

it('withdraws funds from a user wallet via money_in', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();
    $funding = app(FundingService::class);

    $funding->deposit($wallet, 10_000_00);

    $transaction = $funding->withdraw(
        $wallet,
        2_500_00,
        ['narration' => 'Test withdrawal'],
    );

    expect($transaction->type)->toBe(TransactionType::Withdrawal)
        ->and($transaction->isBalanced())->toBeTrue()
        ->and($transaction->ledgerEntries)->toHaveCount(2)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(7_500_00)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(7_500_00)
        ->and($transaction->metadata)->toBe(['narration' => 'Test withdrawal']);
});

it('rejects withdrawals when the wallet has insufficient balance', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();
    $funding = app(FundingService::class);

    $funding->deposit($wallet, 1_000_00);

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    expect(fn () => $funding->withdraw($wallet, 2_000_00))
        ->toThrow(InsufficientBalanceException::class);

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(1_000_00)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(1_000_00);
});

it('rejects deposits into a frozen wallet without side effects', function () {
    $wallet = Account::factory()->frozen()->create();
    $moneyIn = Account::moneyIn();

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();
    $moneyInBalanceBefore = $moneyIn->balanceInMinorUnits();

    expect(fn () => app(FundingService::class)->deposit($wallet, 1_000_00))
        ->toThrow(InvalidTransferException::class, 'Wallet account is not active.');

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(0)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe($moneyInBalanceBefore);
});

it('rejects withdrawals from a closed wallet without side effects', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();
    $funding = app(FundingService::class);

    $funding->deposit($wallet, 5_000_00);
    $wallet->update(['status' => AccountStatus::Closed]);

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    expect(fn () => $funding->withdraw($wallet->fresh(), 1_000_00))
        ->toThrow(InvalidTransferException::class, 'Wallet account is not active.');

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(5_000_00)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(5_000_00);
});

it('rejects deposits when the wallet currency does not match money_in', function () {
    $wallet = Account::factory()->currency('USD')->create();
    $moneyIn = Account::moneyIn();

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    expect(fn () => app(FundingService::class)->deposit($wallet, 1_000_00))
        ->toThrow(InvalidTransferException::class, 'Accounts must share the same currency.');

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(0)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(0);
});

it('rejects non-positive deposit amounts without side effects', function (int $amount) {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    expect(fn () => app(FundingService::class)->deposit($wallet, $amount))
        ->toThrow(InvalidTransferException::class, 'Amount must be a positive integer in minor units.');

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(0)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(0);
})->with([
    'zero' => 0,
    'negative' => -50_00,
]);

it('withdraws the full wallet balance down to zero', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();
    $funding = app(FundingService::class);

    $funding->deposit($wallet, 5_000_00);
    $funding->withdraw($wallet, 5_000_00);

    expect($wallet->fresh()->balanceInMinorUnits())->toBe(0)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(0);
});
