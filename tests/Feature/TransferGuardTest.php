<?php

use App\Enums\AccountType;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Support\Ledger\TransferGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('allows a valid transfer between two active user wallets', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->create();

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($sender, $recipient, 100_00))
        ->not->toThrow(InvalidTransferException::class);
});

it('rejects non-positive amounts', function (int $amount) {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->create();

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($sender, $recipient, $amount))
        ->toThrow(InvalidTransferException::class, 'Amount must be a positive integer in minor units.');
})->with([
    'zero' => 0,
    'negative' => -1,
]);

it('rejects self-transfers', function () {
    $account = Account::factory()->create();

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($account, $account, 100_00))
        ->toThrow(InvalidTransferException::class, 'Cannot transfer to the same account.');
});

it('rejects system accounts as sender', function () {
    $sender = Account::factory()->systemInbound()->create([
        'slug' => 'system_inbound_'.Str::random(8),
    ]);
    $recipient = Account::factory()->create();

    expect($sender->type)->toBe(AccountType::SystemInbound);

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($sender, $recipient, 100_00))
        ->toThrow(InvalidTransferException::class, 'Sender must be a user wallet.');
});

it('rejects system accounts as recipient', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->systemInbound()->create([
        'slug' => 'system_inbound_'.Str::random(8),
    ]);

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($sender, $recipient, 100_00))
        ->toThrow(InvalidTransferException::class, 'Recipient must be a user wallet.');
});

it('rejects frozen sender accounts', function () {
    $sender = Account::factory()->frozen()->create();
    $recipient = Account::factory()->create();

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($sender, $recipient, 100_00))
        ->toThrow(InvalidTransferException::class, 'Sender account is not active.');
});

it('rejects closed recipient accounts', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->closed()->create();

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($sender, $recipient, 100_00))
        ->toThrow(InvalidTransferException::class, 'Recipient account is not active.');
});

it('rejects currency mismatches', function () {
    $sender = Account::factory()->currency('NGN')->create();
    $recipient = Account::factory()->currency('USD')->create();

    expect(fn () => app(TransferGuard::class)->assertCanTransfer($sender, $recipient, 100_00))
        ->toThrow(InvalidTransferException::class, 'Accounts must share the same currency.');
});
