<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Services\User\WalletResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private readonly WalletResolver $wallets,
    ) {}

    public function balance(Request $request, Account $account): JsonResponse
    {
        $wallet = $this->wallets->ownedUserWalletFor($request->user(), $account);

        return response()->json([
            'account_id' => $wallet->id,
            'currency' => $wallet->currency,
            'balance' => $wallet->balanceInMinorUnits(),
        ]);
    }

    public function transactions(Request $request, Account $account): JsonResponse
    {
        $wallet = $this->wallets->ownedUserWalletFor($request->user(), $account);

        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $entries = $wallet->ledgerEntries()
            ->with('transaction')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $entries->getCollection()
                ->map(fn (LedgerEntry $entry): array => $this->transactionItem($entry))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
                'last_page' => $entries->lastPage(),
            ],
        ]);
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     status: string,
     *     amount: int,
     *     currency: string,
     *     direction: string,
     *     narration: string|null,
     *     created_at: string|null
     * }
     */
    private function transactionItem(LedgerEntry $entry): array
    {
        $transaction = $entry->transaction;
        $metadata = $transaction->metadata;
        $narration = is_array($metadata) && isset($metadata['narration']) && is_string($metadata['narration'])
            ? $metadata['narration']
            : null;

        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'status' => $transaction->status->value,
            'amount' => $entry->amount,
            'currency' => $entry->currency,
            'direction' => $entry->direction->value,
            'narration' => $narration,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }
}
