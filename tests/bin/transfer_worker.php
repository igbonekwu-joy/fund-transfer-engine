<?php

/**
 * Child-process worker for concurrent TransferService race tests.
 *
 * Usage: php tests/bin/transfer_worker.php {fromAccountId} {toAccountId} {amount}
 *
 * Exit codes:
 *   0  — transfer succeeded
 *   10 — InsufficientBalanceException
 *   20 — InvalidTransferException
 *   1  — unexpected failure
 */

use App\Exceptions\Ledger\InsufficientBalanceException;
use App\Exceptions\Ledger\InvalidTransferException;
use App\Models\Account;
use App\Services\Ledger\TransferService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$fromId = $argv[1] ?? null;
$toId = $argv[2] ?? null;
$amount = isset($argv[3]) ? (int) $argv[3] : 0;

if (! is_string($fromId) || $fromId === '' || ! is_string($toId) || $toId === '' || $amount <= 0) {
    fwrite(STDERR, "Usage: php tests/bin/transfer_worker.php {fromId} {toId} {amount}\n");
    exit(1);
}

try {
    $from = Account::query()->findOrFail($fromId);
    $to = Account::query()->findOrFail($toId);

    $transaction = $app->make(TransferService::class)->transfer($from, $to, $amount);

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
