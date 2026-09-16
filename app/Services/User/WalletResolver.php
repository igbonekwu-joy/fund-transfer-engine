<?php

namespace App\Services\User;

use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Models\User;

/**
 * Resolve which wallet an authenticated user may use for funding operations.
 *
 * Keeps ownership out of FundingService so the ledger layer stays account-based.
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
}
