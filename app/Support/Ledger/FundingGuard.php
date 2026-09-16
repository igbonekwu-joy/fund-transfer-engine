<?php

namespace App\Support\Ledger;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;

class FundingGuard
{
    /**
     * Assert a deposit into a user wallet via the system inbound boundary is structurally allowed.
     *
     * Does not check balances, locks, or write ledger rows.
     */
    public function assertCanDeposit(Account $wallet, Account $moneyIn, int $amount): void
    {
        $this->assertFundingPair($wallet, $moneyIn, $amount);
    }

    /**
     * Assert a withdrawal from a user wallet via the system inbound boundary is structurally allowed.
     *
     * Does not check balances, locks, or write ledger rows.
     */
    public function assertCanWithdraw(Account $wallet, Account $moneyIn, int $amount): void
    {
        $this->assertFundingPair($wallet, $moneyIn, $amount);
    }

    private function assertFundingPair(Account $wallet, Account $moneyIn, int $amount): void
    {
        $this->assertPositiveAmount($amount);
        $this->assertDistinctAccounts($wallet, $moneyIn);
        $this->assertUserWallet($wallet);
        $this->assertActive($wallet);
        $this->assertSystemInbound($moneyIn);
        $this->assertSameCurrency($wallet, $moneyIn);
    }

    public function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidTransferException('Amount must be a positive integer in minor units.');
        }
    }

    public function assertDistinctAccounts(Account $wallet, Account $moneyIn): void
    {
        if ($wallet->id === $moneyIn->id) {
            throw new InvalidTransferException('Cannot fund the same account as the system boundary.');
        }
    }

    public function assertUserWallet(Account $account): void
    {
        if ($account->type !== AccountType::User) {
            throw new InvalidTransferException('Wallet must be a user account.');
        }
    }

    public function assertActive(Account $account): void
    {
        if ($account->status !== AccountStatus::Active) {
            throw new InvalidTransferException('Wallet account is not active.');
        }
    }

    public function assertSystemInbound(Account $account): void
    {
        if ($account->type !== AccountType::SystemInbound) {
            throw new InvalidTransferException('Funding boundary must be a system inbound account.');
        }
    }

    public function assertSameCurrency(Account $wallet, Account $moneyIn): void
    {
        if ($wallet->currency !== $moneyIn->currency) {
            throw new InvalidTransferException('Accounts must share the same currency.');
        }
    }
}
