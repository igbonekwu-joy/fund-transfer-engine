<?php

use App\Enums\AccountType;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Models\User;
use App\Services\User\WalletResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves the primary wallet for a user', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    $resolved = app(WalletResolver::class)->primaryFor($user);

    expect($resolved->is($wallet))->toBeTrue()
        ->and($resolved->user_id)->toBe($user->id)
        ->and($resolved->type)->toBe(AccountType::User);
});

it('rejects users without a primary wallet', function () {
    $user = User::factory()->create();

    expect($user->account)->toBeNull();

    expect(fn () => app(WalletResolver::class)->primaryFor($user))
        ->toThrow(InvalidTransferException::class, 'User does not have a primary wallet.');
});

it('does not resolve another users wallet as primary', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Account::factory()->for($owner)->create();

    expect(fn () => app(WalletResolver::class)->primaryFor($other))
        ->toThrow(InvalidTransferException::class, 'User does not have a primary wallet.');
});

it('resolves a recipient wallet by account number', function () {
    $recipient = Account::factory()->create();

    $resolved = app(WalletResolver::class)->recipientByAccountNumber($recipient->account_number);

    expect($resolved->is($recipient))->toBeTrue();
});

it('fails when the recipient account number does not exist', function () {
    expect(fn () => app(WalletResolver::class)->recipientByAccountNumber('9999999999'))
        ->toThrow(ModelNotFoundException::class);
});

it('resolves an owned user wallet for account reads', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();

    $resolved = app(WalletResolver::class)->ownedUserWalletFor($user, $wallet);

    expect($resolved->is($wallet))->toBeTrue();
});

it('rejects another users wallet for account reads', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $wallet = Account::factory()->for($owner)->create();

    expect(fn () => app(WalletResolver::class)->ownedUserWalletFor($other, $wallet))
        ->toThrow(ModelNotFoundException::class);
});

it('rejects system accounts for account reads', function () {
    $user = User::factory()->create();
    $moneyIn = Account::factory()->systemInbound()->create([
        'slug' => 'other_inbound',
    ]);

    expect(fn () => app(WalletResolver::class)->ownedUserWalletFor($user, $moneyIn))
        ->toThrow(ModelNotFoundException::class);
});
