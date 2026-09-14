<?php

namespace App\Support\Ledger;

use App\Enums\LedgerEntryDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BalancedLedgerWriter
{
    /**
     * Persist a transaction and its ledger legs only when debits equal credits.
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

        return DB::transaction(function () use ($type, $status, $metadata, $normalized): Transaction {
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
        });
    }
}
