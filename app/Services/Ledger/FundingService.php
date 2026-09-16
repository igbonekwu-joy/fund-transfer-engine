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
use App\Support\Ledger\FundingGuard;
use Illuminate\Support\Facades\DB;

class FundingService
{
    public function __construct(
        private readonly AccountLocker $locker,
        private readonly FundingGuard $guard,
        private readonly BalancedLedgerWriter $writer,
    ) {}

    /**
     * Credit a user wallet by debiting the money_in system boundary.
     *
     * Retries once on database deadlock / serialization failures.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function deposit(
        Account $wallet,
        int $amount,
        array $metadata = [],
    ): Transaction {
        return DB::transaction(function () use ($wallet, $amount, $metadata): Transaction {
            [$lockedWallet, $moneyIn] = $this->lockFundingAccounts($wallet);

            $this->guard->assertCanDeposit($lockedWallet, $moneyIn, $amount);

            return $this->writer->postWithinTransaction(
                TransactionType::Deposit,
                [
                    [
                        'account_id' => $moneyIn->id,
                        'direction' => LedgerEntryDirection::Debit,
                        'amount' => $amount,
                        'currency' => $lockedWallet->currency,
                    ],
                    [
                        'account_id' => $lockedWallet->id,
                        'direction' => LedgerEntryDirection::Credit,
                        'amount' => $amount,
                        'currency' => $lockedWallet->currency,
                    ],
                ],
                TransactionStatus::Posted,
                $metadata,
            );
        }, 2);
    }

    /**
     * Debit a user wallet by crediting the money_in system boundary.
     *
     * Retries once on database deadlock / serialization failures.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function withdraw(
        Account $wallet,
        int $amount,
        array $metadata = [],
    ): Transaction {
        return DB::transaction(function () use ($wallet, $amount, $metadata): Transaction {
            [$lockedWallet, $moneyIn] = $this->lockFundingAccounts($wallet);

            $this->guard->assertCanWithdraw($lockedWallet, $moneyIn, $amount);

            if ($lockedWallet->balanceInMinorUnits() < $amount) {
                throw new InsufficientBalanceException;
            }

            return $this->writer->postWithinTransaction(
                TransactionType::Withdrawal,
                [
                    [
                        'account_id' => $lockedWallet->id,
                        'direction' => LedgerEntryDirection::Debit,
                        'amount' => $amount,
                        'currency' => $lockedWallet->currency,
                    ],
                    [
                        'account_id' => $moneyIn->id,
                        'direction' => LedgerEntryDirection::Credit,
                        'amount' => $amount,
                        'currency' => $lockedWallet->currency,
                    ],
                ],
                TransactionStatus::Posted,
                $metadata,
            );
        }, 2);
    }

    /**
     * @return array{0: Account, 1: Account}
     */
    private function lockFundingAccounts(Account $wallet): array
    {
        $moneyInId = Account::moneyIn()->id;
        $locked = $this->locker->lockWithMoneyIn($wallet);

        /** @var Account $lockedWallet */
        $lockedWallet = $locked->get($wallet->id);

        /** @var Account $moneyIn */
        $moneyIn = $locked->get($moneyInId);

        return [$lockedWallet, $moneyIn];
    }
}
