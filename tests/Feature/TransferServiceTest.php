<?php

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Services\Ledger\TransferService;
use App\Support\Ledger\AccountLocker;
use App\Support\Ledger\BalancedLedgerWriter;
use App\Support\Ledger\TransferGuard;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

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

it('rolls back mid-flight posting failures with zero side effects', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->create();

    fundAccount($sender, 10_000_00);

    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    $failingWriter = new class extends BalancedLedgerWriter
    {
        public function postWithinTransaction(
            TransactionType $type,
            array $entries,
            TransactionStatus $status = TransactionStatus::Posted,
            array $metadata = [],
        ): Transaction {
            if (DB::transactionLevel() < 1) {
                throw new LogicException('postWithinTransaction requires an open database transaction.');
            }

            $transaction = Transaction::query()->create([
                'type' => $type,
                'status' => $status,
                'metadata' => $metadata === [] ? null : $metadata,
            ]);

            $first = $entries[0];

            LedgerEntry::query()->create([
                'transaction_id' => $transaction->id,
                'account_id' => $first['account_id'],
                'direction' => $first['direction'],
                'amount' => $first['amount'],
                'currency' => is_string($first['currency'] ?? null) ? $first['currency'] : 'NGN',
            ]);

            throw new RuntimeException('mid-flight failure');
        }
    };

    $service = new TransferService(
        app(AccountLocker::class),
        app(TransferGuard::class),
        $failingWriter,
    );

    expect(fn () => $service->transfer($sender, $recipient, 2_500_00))
        ->toThrow(RuntimeException::class, 'mid-flight failure');

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($sender->fresh()->balanceInMinorUnits())->toBe(10_000_00)
        ->and($recipient->fresh()->balanceInMinorUnits())->toBe(0);
});
