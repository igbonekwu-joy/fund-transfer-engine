<?php

namespace App\Exceptions\Ledger;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientBalanceException extends Exception implements ShouldntReport
{
    public function __construct(string $message = 'Insufficient balance.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
