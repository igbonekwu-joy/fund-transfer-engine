<?php

namespace App\Services\User;

use App\Enums\AccountType;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Resolve which wallets an authenticated user may use for funding and transfers.
 *
 * Keeps ownership out of ledger services so those stay account-based.
 */
class WalletResolver
{
    /**
     * Resolve the user's primary user wallet.
     *
     * @throws InvalidTransferException When the user has no primary wallet yet.
     */
    public function primaryFor(User $user): Account
    {
        $wallet = $user->account;

        if ($wallet === null) {
            throw new InvalidTransferException('User does not have a primary wallet.');
        }

        return $wallet;
    }

    /**
     * Resolve a recipient user wallet by public account number.
     *
     * @throws ModelNotFoundException When no matching user wallet exists.
     */
    public function recipientByAccountNumber(string $accountNumber): Account
    {
        return Account::query()
            ->where('account_number', $accountNumber)
            ->where('type', AccountType::User)
            ->firstOrFail();
    }
}
