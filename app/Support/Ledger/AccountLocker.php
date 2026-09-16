<?php

namespace App\Support\Ledger;

use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class AccountLocker
{
    /**
     * Lock two accounts for update in a deadlock-safe order.
     *
     * Only call this inside DB::transaction().
     *
     * @return Collection<string, Account>
     */
    public function lockPair(string $firstId, string $secondId): Collection
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('AccountLocker requires an open database transaction.');
        }

        if ($firstId === $secondId) {
            throw new InvalidTransferException('Cannot lock the same account twice for a transfer.');
        }

        $ids = collect([$firstId, $secondId])->sort()->values();

        $accounts = Account::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ([$firstId, $secondId] as $id) {
            if (! $accounts->has($id)) {
                throw new InvalidTransferException('Account not found.');
            }
        }

        return $accounts;
    }

    /**
     * Lock a user wallet together with the money_in system boundary.
     *
     * Only call this inside DB::transaction().
     *
     * @return Collection<string, Account>
     */
    public function lockWithMoneyIn(Account $wallet): Collection
    {
        $moneyIn = Account::moneyIn();

        return $this->lockPair($wallet->id, $moneyIn->id);
    }
}
