<?php

use App\Enums\TransactionType;
use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Ledger\FundingService;
use Database\Seeders\SystemAccountsSeeder;

uses()->group('concurrency');

beforeEach(function () {
    if (! databaseSupportsRowLevelLocks()) {
        $this->markTestSkipped(
            'Concurrent funding tests require MySQL or PostgreSQL row-level locks (not SQLite).'
        );
    }

    $this->artisan('migrate:fresh', ['--force' => true]);
    $this->seed(SystemAccountsSeeder::class);
});

it('prevents double-spend when two concurrent withdrawals exhaust the wallet', function () {
    $wallet = Account::factory()->create();

    app(FundingService::class)->deposit($wallet, 10_000_00);

    $results = runConcurrentFunding([
        ['wallet' => $wallet->id, 'amount' => 10_000_00, 'operation' => 'withdraw'],
        ['wallet' => $wallet->id, 'amount' => 10_000_00, 'operation' => 'withdraw'],
    ]);

    $exitCodes = collect($results)->pluck('exit_code')->sort()->values()->all();

    expect($exitCodes)->toBe([0, 10])
        ->and(collect($results)->where('ok', true))->toHaveCount(1)
        ->and(collect($results)->where('exception', InsufficientBalanceException::class))->toHaveCount(1)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(0)
        ->and(Account::moneyIn()->balanceInMinorUnits())->toBe(0)
        ->and(Transaction::query()->where('type', TransactionType::Withdrawal)->count())->toBe(1);
});

it('allows concurrent withdrawals when the wallet has enough funds for both', function () {
    $wallet = Account::factory()->create();

    app(FundingService::class)->deposit($wallet, 10_000_00);

    $results = runConcurrentFunding([
        ['wallet' => $wallet->id, 'amount' => 4_000_00, 'operation' => 'withdraw'],
        ['wallet' => $wallet->id, 'amount' => 4_000_00, 'operation' => 'withdraw'],
    ]);

    expect(collect($results)->every(fn (array $result): bool => $result['ok']))->toBeTrue()
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(2_000_00)
        ->and(Account::moneyIn()->balanceInMinorUnits())->toBe(2_000_00)
        ->and(Transaction::query()->where('type', TransactionType::Withdrawal)->count())->toBe(2);
});
