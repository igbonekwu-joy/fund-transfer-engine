<?php

namespace App\Support\Ledger;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;

class TransferGuard
{
    /**
     * Assert the proposed peer transfer is structurally allowed.
     *
     * Does not check balances, locks, or write ledger rows.
     */
    public function assertCanTransfer(Account $sender, Account $recipient, int $amount): void
    {
        $this->assertPositiveAmount($amount);
        $this->assertDistinctAccounts($sender, $recipient);
        $this->assertUserWallet($sender, 'Sender');
        $this->assertUserWallet($recipient, 'Recipient');
        $this->assertActive($sender, 'Sender');
        $this->assertActive($recipient, 'Recipient');
        $this->assertSameCurrency($sender, $recipient);
    }

    public function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidTransferException('Amount must be a positive integer in minor units.');
        }
    }

    public function assertDistinctAccounts(Account $sender, Account $recipient): void
    {
        if ($sender->id === $recipient->id) {
            throw new InvalidTransferException('Cannot transfer to the same account.');
        }
    }

    public function assertUserWallet(Account $account, string $role = 'Account'): void
    {
        if ($account->type !== AccountType::User) {
            throw new InvalidTransferException("{$role} must be a user wallet.");
        }
    }

    public function assertActive(Account $account, string $role = 'Account'): void
    {
        if ($account->status !== AccountStatus::Active) {
            throw new InvalidTransferException("{$role} account is not active.");
        }
    }

    public function assertSameCurrency(Account $sender, Account $recipient): void
    {
        if ($sender->currency !== $recipient->currency) {
            throw new InvalidTransferException('Accounts must share the same currency.');
        }
    }
}
