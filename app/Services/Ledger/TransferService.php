<?php

namespace App\Services\Ledger;

use App\Enums\LedgerEntryDirection;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\Ledger\AccountLocker;
use App\Support\Ledger\BalancedLedgerWriter;
use App\Support\Ledger\TransferGuard;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(
        private readonly AccountLocker $locker,
        private readonly TransferGuard $guard,
        private readonly BalancedLedgerWriter $writer,
    ) {}

    /**
     * Move funds between two user wallets atomically.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function transfer(
        Account $from,
        Account $to,
        int $amount,
        array $metadata = [],
    ): Transaction {
        return DB::transaction(function () use ($from, $to, $amount, $metadata): Transaction {
            $locked = $this->locker->lockPair($from->id, $to->id);

            /** @var Account $sender */
            $sender = $locked->get($from->id);

            /** @var Account $recipient */
            $recipient = $locked->get($to->id);

            $this->guard->assertCanTransfer($sender, $recipient, $amount);

            if ($sender->balanceInMinorUnits() < $amount) {
                throw new InsufficientBalanceException;
            }

            return $this->writer->postWithinTransaction(
                TransactionType::Transfer,
                [
                    [
                        'account_id' => $sender->id,
                        'direction' => LedgerEntryDirection::Debit,
                        'amount' => $amount,
                        'currency' => $sender->currency,
                    ],
                    [
                        'account_id' => $recipient->id,
                        'direction' => LedgerEntryDirection::Credit,
                        'amount' => $amount,
                        'currency' => $recipient->currency,
                    ],
                ],
                TransactionStatus::Posted,
                $metadata,
            );
        });
    }
}
