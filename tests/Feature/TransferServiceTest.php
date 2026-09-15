<?php

use App\Enums\LedgerEntryDirection;
use App\Enums\TransactionType;
use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Services\Ledger\TransferService;
use App\Support\Ledger\BalancedLedgerWriter;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

/**
 * Credit a user wallet via the money_in system boundary.
 */
function fundAccount(Account $wallet, int $amount): void
{
    $moneyIn = Account::moneyIn();

    app(BalancedLedgerWriter::class)->post(
        TransactionType::Deposit,
        [
            [
                'account_id' => $moneyIn->id,
                'direction' => LedgerEntryDirection::Debit,
                'amount' => $amount,
                'currency' => $wallet->currency,
            ],
            [
                'account_id' => $wallet->id,
                'direction' => LedgerEntryDirection::Credit,
                'amount' => $amount,
                'currency' => $wallet->currency,
            ],
        ],
    );
}

it('transfers funds between two user wallets', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->create();

    fundAccount($sender, 10_000_00);

    $transaction = app(TransferService::class)->transfer(
        $sender,
        $recipient,
        2_500_00,
        ['narration' => 'Test transfer'],
    );

    expect($transaction->type)->toBe(TransactionType::Transfer)
        ->and($transaction->isBalanced())->toBeTrue()
        ->and($transaction->ledgerEntries)->toHaveCount(2)
        ->and($sender->fresh()->balanceInMinorUnits())->toBe(7_500_00)
        ->and($recipient->fresh()->balanceInMinorUnits())->toBe(2_500_00)
        ->and($transaction->metadata)->toBe(['narration' => 'Test transfer']);
});

it('rejects transfers when the sender has insufficient balance', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->create();

    fundAccount($sender, 1_000_00);

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    expect(fn () => app(TransferService::class)->transfer($sender, $recipient, 2_000_00))
        ->toThrow(InsufficientBalanceException::class);

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($sender->fresh()->balanceInMinorUnits())->toBe(1_000_00)
        ->and($recipient->fresh()->balanceInMinorUnits())->toBe(0);
});

it('rejects self-transfers without writing transfer rows', function () {
    $account = Account::factory()->create();

    fundAccount($account, 5_000_00);

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    expect(fn () => app(TransferService::class)->transfer($account, $account, 1_000_00))
        ->toThrow(InvalidTransferException::class);

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($account->fresh()->balanceInMinorUnits())->toBe(5_000_00);
});
