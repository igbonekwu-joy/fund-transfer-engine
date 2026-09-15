<?php

namespace App\Support\Ledger;

use App\Enums\LedgerEntryDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class BalancedLedgerWriter
{
    /**
     * Persist a balanced ledger posting inside its own database transaction.
     *
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, mixed>  $metadata
     */
    public function post(
        TransactionType $type,
        array $entries,
        TransactionStatus $status = TransactionStatus::Posted,
        array $metadata = [],
    ): Transaction {
        return DB::transaction(
            fn (): Transaction => $this->postWithinTransaction($type, $entries, $status, $metadata)
        );
    }

    /**
     * Persist a balanced ledger posting using the caller's open database transaction.
     *
     * Only call this inside DB::transaction(). Prefer post() when you are not
     * coordinating locks or other work in the same unit of work.
     *
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, mixed>  $metadata
     */
    public function postWithinTransaction(
        TransactionType $type,
        array $entries,
        TransactionStatus $status = TransactionStatus::Posted,
        array $metadata = [],
    ): Transaction {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('postWithinTransaction requires an open database transaction.');
        }

        $normalized = $this->normalize($entries);

        $transaction = Transaction::query()->create([
            'type' => $type,
            'status' => $status,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        foreach ($normalized as $entry) {
            LedgerEntry::query()->create([
                'transaction_id' => $transaction->id,
                'account_id' => $entry['account_id'],
                'direction' => $entry['direction'],
                'amount' => $entry['amount'],
                'currency' => $entry['currency'],
            ]);
        }

        return $transaction->load('ledgerEntries');
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return list<array{account_id: string, direction: LedgerEntryDirection, amount: int, currency: string}>
     */
    private function normalize(array $entries): array
    {
        if (count($entries) < 2) {
            throw new InvalidArgumentException('A ledger posting requires at least two entries.');
        }

        $debitTotal = 0;
        $creditTotal = 0;
        $normalized = [];

        foreach ($entries as $index => $entry) {
            $accountId = $entry['account_id'] ?? null;
            $amount = $entry['amount'] ?? null;
            $directionValue = $entry['direction'] ?? null;

            if (! is_string($accountId) || $accountId === '') {
                throw new InvalidArgumentException("Ledger entry [{$index}] account_id must be a non-empty string.");
            }

            if (! is_int($amount) || $amount <= 0) {
                throw new InvalidArgumentException("Ledger entry [{$index}] amount must be a positive integer in minor units.");
            }

            $direction = $directionValue instanceof LedgerEntryDirection
                ? $directionValue
                : LedgerEntryDirection::from((string) $directionValue);

            if ($direction === LedgerEntryDirection::Debit) {
                $debitTotal += $amount;
            } else {
                $creditTotal += $amount;
            }

            $normalized[] = [
                'account_id' => $accountId,
                'direction' => $direction,
                'amount' => $amount,
                'currency' => is_string($entry['currency'] ?? null) ? $entry['currency'] : 'NGN',
            ];
        }

        if ($debitTotal !== $creditTotal) {
            throw new InvalidArgumentException(
                "Unbalanced ledger posting: debits [{$debitTotal}] do not equal credits [{$creditTotal}]."
            );
        }

        return $normalized;
    }
}
