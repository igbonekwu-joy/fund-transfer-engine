<?php

namespace App\Exceptions\Ledger;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidTransferException extends Exception implements ShouldntReport
{
    public function __construct(string $message = 'Invalid transfer.')
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
