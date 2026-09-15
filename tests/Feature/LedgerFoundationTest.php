<?php

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\LedgerEntryDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Ledger\BalancedLedgerWriter;
use Database\Seeders\SystemAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SystemAccountsSeeder::class);
});

it('seeds the system money_in account as the inbound boundary', function () {
    $moneyIn = Account::moneyIn();

    expect($moneyIn->slug)->toBe(Account::MONEY_IN_SLUG)
        ->and($moneyIn->type)->toBe(AccountType::SystemInbound)
        ->and($moneyIn->status)->toBe(AccountStatus::Active)
        ->and($moneyIn->user_id)->toBeNull()
        ->and($moneyIn->currency)->toBe('NGN')
        ->and($moneyIn->account_number)->toBeNull();
});

it('stores the user account number on accounts instead of users', function () {
    $user = User::factory()->create();

    $account = $user->account()->create([
        'type' => AccountType::User,
        'status' => AccountStatus::Active,
        'currency' => 'NGN',
        'account_number' => '1234567890',
        'name' => 'Primary wallet',
    ]);

    expect(array_key_exists('account_number', $user->fresh()->getAttributes()))->toBeFalse()
        ->and($account->account_number)->toBe('1234567890')
        ->and($user->fresh(['account'])->toApiArray()['account_number'])->toBe('1234567890')
        ->and(Schema::hasColumn('users', 'account_number'))->toBeFalse();
});

it('posts a balanced deposit against the money_in system account', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();
    $moneyIn = Account::moneyIn();
    $amount = 10_000_00;

    $transaction = app(BalancedLedgerWriter::class)->post(
        TransactionType::Deposit,
        [
            [
                'account_id' => $moneyIn->id,
                'direction' => LedgerEntryDirection::Debit,
                'amount' => $amount,
            ],
            [
                'account_id' => $wallet->id,
                'direction' => LedgerEntryDirection::Credit,
                'amount' => $amount,
            ],
        ],
        TransactionStatus::Posted,
        ['narration' => 'Test deposit'],
    );

    expect($transaction->ledgerEntries)->toHaveCount(2)
        ->and($transaction->isBalanced())->toBeTrue()
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe($amount)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe($amount);
});

it('rejects unbalanced ledger postings without writing rows', function () {
    $user = User::factory()->create();
    $wallet = Account::factory()->for($user)->create();
    $moneyIn = Account::moneyIn();

    expect(fn () => app(BalancedLedgerWriter::class)->post(
        TransactionType::Deposit,
        [
            [
                'account_id' => $moneyIn->id,
                'direction' => LedgerEntryDirection::Debit,
                'amount' => 5_000_00,
            ],
            [
                'account_id' => $wallet->id,
                'direction' => LedgerEntryDirection::Credit,
                'amount' => 4_000_00,
            ],
        ],
    ))->toThrow(InvalidArgumentException::class, 'Unbalanced ledger posting');

    expect(Transaction::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->count())->toBe(0);
});

it('posts a balanced peer transfer between two user accounts', function () {
    $sender = Account::factory()->create();
    $recipient = Account::factory()->create();
    $amount = 2_500_00;

    $transaction = app(BalancedLedgerWriter::class)->post(
        TransactionType::Transfer,
        [
            [
                'account_id' => $sender->id,
                'direction' => LedgerEntryDirection::Debit,
                'amount' => $amount,
            ],
            [
                'account_id' => $recipient->id,
                'direction' => LedgerEntryDirection::Credit,
                'amount' => $amount,
            ],
        ],
    );

    expect($transaction->isBalanced())->toBeTrue()
        ->and($sender->fresh()->balanceInMinorUnits())->toBe(-$amount)
        ->and($recipient->fresh()->balanceInMinorUnits())->toBe($amount);
});

it('prevents updating or deleting ledger entries', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();

    $transaction = app(BalancedLedgerWriter::class)->post(
        TransactionType::Deposit,
        [
            [
                'account_id' => $moneyIn->id,
                'direction' => LedgerEntryDirection::Debit,
                'amount' => 1_000_00,
            ],
            [
                'account_id' => $wallet->id,
                'direction' => LedgerEntryDirection::Credit,
                'amount' => 1_000_00,
            ],
        ],
    );

    $entry = $transaction->ledgerEntries->firstOrFail();

    expect(fn () => $entry->update(['amount' => 2_000_00]))
        ->toThrow(RuntimeException::class, 'immutable');

    expect(fn () => $entry->delete())
        ->toThrow(RuntimeException::class, 'immutable');
});

it('rolls back postWithinTransaction writes with the outer database transaction', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();
    $writer = app(BalancedLedgerWriter::class);

    try {
        DB::transaction(function () use ($writer, $wallet, $moneyIn): void {
            $writer->postWithinTransaction(
                TransactionType::Deposit,
                [
                    [
                        'account_id' => $moneyIn->id,
                        'direction' => LedgerEntryDirection::Debit,
                        'amount' => 1_000_00,
                    ],
                    [
                        'account_id' => $wallet->id,
                        'direction' => LedgerEntryDirection::Credit,
                        'amount' => 1_000_00,
                    ],
                ],
            );

            throw new RuntimeException('force outer rollback');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('force outer rollback');
    }

    expect(Transaction::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->count())->toBe(0)
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe(0)
        ->and($moneyIn->fresh()->balanceInMinorUnits())->toBe(0);
});

it('allows postWithinTransaction inside an open database transaction', function () {
    $wallet = Account::factory()->create();
    $moneyIn = Account::moneyIn();
    $amount = 750_00;

    $transaction = DB::transaction(
        fn (): Transaction => app(BalancedLedgerWriter::class)->postWithinTransaction(
            TransactionType::Deposit,
            [
                [
                    'account_id' => $moneyIn->id,
                    'direction' => LedgerEntryDirection::Debit,
                    'amount' => $amount,
                ],
                [
                    'account_id' => $wallet->id,
                    'direction' => LedgerEntryDirection::Credit,
                    'amount' => $amount,
                ],
            ],
        )
    );

    expect($transaction->isBalanced())->toBeTrue()
        ->and($wallet->fresh()->balanceInMinorUnits())->toBe($amount)
        ->and(Transaction::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->count())->toBe(2);
});
