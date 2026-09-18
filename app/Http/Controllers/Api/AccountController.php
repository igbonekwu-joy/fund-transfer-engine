<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
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
}
