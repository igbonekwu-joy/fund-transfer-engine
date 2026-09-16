<?php

use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Support\Ledger\AccountLocker;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('locks and returns both accounts keyed by id', function () {
    $first = Account::factory()->create();
    $second = Account::factory()->create();

    $locked = DB::transaction(
        fn () => app(AccountLocker::class)->lockPair($first->id, $second->id)
    );

    expect($locked)->toHaveCount(2)
        ->and($locked->get($first->id)->is($first))->toBeTrue()
        ->and($locked->get($second->id)->is($second))->toBeTrue();
});

it('returns the same accounts regardless of argument order', function () {
    $first = Account::factory()->create();
    $second = Account::factory()->create();
    $locker = app(AccountLocker::class);

    $forward = DB::transaction(fn () => $locker->lockPair($first->id, $second->id));
    $reverse = DB::transaction(fn () => $locker->lockPair($second->id, $first->id));

    expect($forward->keys()->sort()->values()->all())
        ->toBe($reverse->keys()->sort()->values()->all())
        ->and($forward->get($first->id)->id)->toBe($first->id)
        ->and($reverse->get($first->id)->id)->toBe($first->id);
});

it('throws when an account is missing', function () {
    $existing = Account::factory()->create();
    $missingId = (string) Str::uuid();

    expect(fn () => DB::transaction(
        fn () => app(AccountLocker::class)->lockPair($existing->id, $missingId)
    ))->toThrow(InvalidTransferException::class, 'Account not found.');
});

it('throws when both account ids are the same', function () {
    $account = Account::factory()->create();

    expect(fn () => DB::transaction(
        fn () => app(AccountLocker::class)->lockPair($account->id, $account->id)
    ))->toThrow(InvalidTransferException::class, 'Cannot lock the same account twice');
});

it('locks a wallet together with the money_in boundary', function () {
    $this->seed(SystemAccountsSeeder::class);

    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    $locked = DB::transaction(
        fn () => app(AccountLocker::class)->lockWithMoneyIn($wallet)
    );

    expect($locked)->toHaveCount(2)
        ->and($locked->get($wallet->id)->is($wallet))->toBeTrue()
        ->and($locked->get($moneyIn->id)->is($moneyIn))->toBeTrue();
});
