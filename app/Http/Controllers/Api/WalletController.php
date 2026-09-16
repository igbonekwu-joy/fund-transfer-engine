<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\FundingRequest;
use App\Http\Requests\Wallet\TransferRequest;
use App\Models\Transaction;
use App\Services\Ledger\FundingService;
use App\Services\Ledger\TransferService;
use App\Services\User\WalletResolver;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function __construct(
        private readonly FundingService $funding,
        private readonly TransferService $transfers,
        private readonly WalletResolver $wallets,
    ) {}

    public function deposit(FundingRequest $request): JsonResponse
    {
        $wallet = $this->wallets->primaryFor($request->user());

        $transaction = $this->funding->deposit(
            $wallet,
            $request->integer('amount'),
            $request->metadata(),
        );

        return response()->json([
            'message' => 'Deposit successful.',
            'transaction' => $this->transactionPayload($transaction),
            'balance' => $wallet->fresh()->balanceInMinorUnits(),
        ]);
    }

    public function withdraw(FundingRequest $request): JsonResponse
    {
        $wallet = $this->wallets->primaryFor($request->user());

        $transaction = $this->funding->withdraw(
            $wallet,
            $request->integer('amount'),
            $request->metadata(),
        );

        return response()->json([
            'message' => 'Withdrawal successful.',
            'transaction' => $this->transactionPayload($transaction),
            'balance' => $wallet->fresh()->balanceInMinorUnits(),
        ]);
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $sender = $this->wallets->primaryFor($request->user());
        $recipient = $this->wallets->recipientByAccountNumber(
            $request->string('account_number')->toString(),
        );

        $transaction = $this->transfers->transfer(
            $sender,
            $recipient,
            $request->integer('amount'),
            $request->metadata(),
        );

        return response()->json([
            'message' => 'Transfer successful.',
            'transaction' => $this->transactionPayload($transaction),
            'balance' => $sender->fresh()->balanceInMinorUnits(),
        ]);
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     status: string,
     *     metadata: array<string, mixed>|null,
     *     amount: int,
     *     currency: string,
     *     created_at: string|null
     * }
     */
    private function transactionPayload(Transaction $transaction): array
    {
        $entry = $transaction->ledgerEntries->firstOrFail();

        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'status' => $transaction->status->value,
            'metadata' => $transaction->metadata,
            'amount' => $entry->amount,
            'currency' => $entry->currency,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }
}
