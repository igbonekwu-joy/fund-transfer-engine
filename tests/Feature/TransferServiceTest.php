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

it('rejects transfers from a frozen sender without side effects', function () {
    $sender = Account::factory()->frozen()->create();
    $recipient = Account::factory()->create();

    fundAccount($sender, 5_000_00);

    assertTransferRejectedWithoutSideEffects(
        $sender,
        $recipient,
        1_000_00,
        InvalidTransferException::class,
        'Sender account is not active.',
        senderBalance: 5_000_00,
        recipientBalance: 0,
    );
});

it('rejects transfers to a closed recipient without side effects', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->closed()->create();

    fundAccount($sender, 5_000_00);

    assertTransferRejectedWithoutSideEffects(
        $sender,
        $recipient,
        1_000_00,
        InvalidTransferException::class,
        'Recipient account is not active.',
        senderBalance: 5_000_00,
        recipientBalance: 0,
    );
});

it('rejects currency mismatches without side effects', function () {
    $sender = Account::factory()->currency('NGN')->create();
    $recipient = Account::factory()->currency('USD')->create();

    fundAccount($sender, 5_000_00);

    assertTransferRejectedWithoutSideEffects(
        $sender,
        $recipient,
        1_000_00,
        InvalidTransferException::class,
        'Accounts must share the same currency.',
        senderBalance: 5_000_00,
        recipientBalance: 0,
    );
});

it('rejects transfers that debit the money_in system account', function () {
    $moneyIn = Account::moneyIn();
    $recipient = Account::factory()->create();

    assertTransferRejectedWithoutSideEffects(
        $moneyIn,
        $recipient,
        1_000_00,
        InvalidTransferException::class,
        'Sender must be a user wallet.',
        senderBalance: $moneyIn->balanceInMinorUnits(),
        recipientBalance: 0,
    );
});

it('rejects transfers that credit the money_in system account', function () {
    $sender = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    fundAccount($sender, 5_000_00);

    assertTransferRejectedWithoutSideEffects(
        $sender,
        $moneyIn,
        1_000_00,
        InvalidTransferException::class,
        'Recipient must be a user wallet.',
        senderBalance: 5_000_00,
        recipientBalance: $moneyIn->balanceInMinorUnits(),
    );
});

it('rejects non-positive transfer amounts without side effects', function (int $amount) {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->create();

    fundAccount($sender, 5_000_00);

    assertTransferRejectedWithoutSideEffects(
        $sender,
        $recipient,
        $amount,
        InvalidTransferException::class,
        'Amount must be a positive integer in minor units.',
        senderBalance: 5_000_00,
        recipientBalance: 0,
    );
})->with([
    'zero' => 0,
    'negative' => -50_00,
]);

/**
 * @param  class-string<Throwable>  $exceptionClass
 */
function assertTransferRejectedWithoutSideEffects(
    Account $from,
    Account $to,
    int $amount,
    string $exceptionClass,
    string $message,
    int $senderBalance,
    int $recipientBalance,
): void {
    $transactionsBefore = Transaction::query()->count();
    $entriesBefore = LedgerEntry::query()->count();

    expect(fn () => app(TransferService::class)->transfer($from, $to, $amount))
        ->toThrow($exceptionClass, $message);

    expect(Transaction::query()->count())->toBe($transactionsBefore)
        ->and(LedgerEntry::query()->count())->toBe($entriesBefore)
        ->and($from->fresh()->balanceInMinorUnits())->toBe($senderBalance)
        ->and($to->fresh()->balanceInMinorUnits())->toBe($recipientBalance);
}
