<?php

use App\Enums\TransactionType;
use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use Database\Seeders\SystemAccountsSeeder;

uses()->group('concurrency');

beforeEach(function () {
    if (! databaseSupportsRowLevelLocks()) {
        $this->markTestSkipped(
            'Concurrent transfer tests require MySQL or PostgreSQL row-level locks (not SQLite).'
        );
    }

    $this->artisan('migrate:fresh', ['--force' => true]);
    $this->seed(SystemAccountsSeeder::class);
});

it('prevents double-spend when two concurrent transfers exhaust the sender', function () {
    $sender = Account::factory()->create();
    $firstRecipient = Account::factory()->create();
    $secondRecipient = Account::factory()->create();

    fundAccount($sender, 10_000_00);

    $results = runConcurrentTransfers([
        ['from' => $sender->id, 'to' => $firstRecipient->id, 'amount' => 10_000_00],
        ['from' => $sender->id, 'to' => $secondRecipient->id, 'amount' => 10_000_00],
    ]);

    $exitCodes = collect($results)->pluck('exit_code')->sort()->values()->all();

    expect($exitCodes)->toBe([0, 10])
        ->and(collect($results)->where('ok', true))->toHaveCount(1)
        ->and(collect($results)->where('exception', InsufficientBalanceException::class))->toHaveCount(1)
        ->and($sender->fresh()->balanceInMinorUnits())->toBe(0)
        ->and(
            $firstRecipient->fresh()->balanceInMinorUnits()
            + $secondRecipient->fresh()->balanceInMinorUnits()
        )->toBe(10_000_00)
        ->and(Transaction::query()->where('type', TransactionType::Transfer)->count())->toBe(1);
});

it('allows concurrent transfers when the sender has enough funds for both', function () {
    $sender = Account::factory()->create();
    $firstRecipient = Account::factory()->create();
    $secondRecipient = Account::factory()->create();

    fundAccount($sender, 10_000_00);

    $results = runConcurrentTransfers([
        ['from' => $sender->id, 'to' => $firstRecipient->id, 'amount' => 4_000_00],
        ['from' => $sender->id, 'to' => $secondRecipient->id, 'amount' => 4_000_00],
    ]);

    expect(collect($results)->every(fn (array $result): bool => $result['ok']))->toBeTrue()
        ->and($sender->fresh()->balanceInMinorUnits())->toBe(2_000_00)
        ->and($firstRecipient->fresh()->balanceInMinorUnits())->toBe(4_000_00)
        ->and($secondRecipient->fresh()->balanceInMinorUnits())->toBe(4_000_00)
        ->and(Transaction::query()->where('type', TransactionType::Transfer)->count())->toBe(2);
});

it('completes opposing concurrent transfers without deadlock', function () {
    $alice = Account::factory()->create();
    $bob = Account::factory()->create();

    fundAccount($alice, 5_000_00);
    fundAccount($bob, 5_000_00);

    $results = runConcurrentTransfers([
        ['from' => $alice->id, 'to' => $bob->id, 'amount' => 1_500_00],
        ['from' => $bob->id, 'to' => $alice->id, 'amount' => 2_000_00],
    ]);

    expect(collect($results)->every(fn (array $result): bool => $result['ok']))->toBeTrue()
        ->and($alice->fresh()->balanceInMinorUnits())->toBe(5_500_00)
        ->and($bob->fresh()->balanceInMinorUnits())->toBe(4_500_00)
        ->and(Transaction::query()->where('type', TransactionType::Transfer)->count())->toBe(2);
});
