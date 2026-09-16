<?php

/**
 * Child-process worker for concurrent FundingService race tests.
 *
 * Usage: php tests/bin/funding_worker.php {walletId} {amount} {deposit|withdraw}
 *
 * Exit codes:
 *   0  — operation succeeded
 *   10 — InsufficientBalanceException
 *   20 — InvalidTransferException
 *   1  — unexpected failure
 */

use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Services\Ledger\FundingService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$walletId = $argv[1] ?? null;
$amount = isset($argv[2]) ? (int) $argv[2] : 0;
$operation = $argv[3] ?? null;

if (
    ! is_string($walletId) || $walletId === ''
    || $amount <= 0
    || ! in_array($operation, ['deposit', 'withdraw'], true)
) {
    fwrite(STDERR, "Usage: php tests/bin/funding_worker.php {walletId} {amount} {deposit|withdraw}\n");
    exit(1);
}

try {
    $wallet = Account::query()->findOrFail($walletId);
    $funding = $app->make(FundingService::class);

    $transaction = match ($operation) {
        'deposit' => $funding->deposit($wallet, $amount),
        'withdraw' => $funding->withdraw($wallet, $amount),
    };

    echo json_encode([
        'ok' => true,
        'transaction_id' => $transaction->id,
    ], JSON_THROW_ON_ERROR).PHP_EOL;

    exit(0);
} catch (InsufficientBalanceException $exception) {
    echo json_encode([
        'ok' => false,
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR).PHP_EOL;

    exit(10);
} catch (InvalidTransferException $exception) {
    echo json_encode([
        'ok' => false,
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR).PHP_EOL;

    exit(20);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
