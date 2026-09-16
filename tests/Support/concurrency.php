<?php

use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\Pool;

/**
 * True when the default connection can honor SELECT ... FOR UPDATE across sessions.
 */
function databaseSupportsRowLevelLocks(): bool
{
    return in_array(config('database.default'), ['mysql', 'mariadb', 'pgsql'], true);
}

/**
 * @return array<string, string>
 */
function databaseEnvForWorkers(): array
{
    $connection = config('database.default');
    $config = config("database.connections.{$connection}");

    return [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => (string) $connection,
        'DB_HOST' => (string) ($config['host'] ?? '127.0.0.1'),
        'DB_PORT' => (string) ($config['port'] ?? '3306'),
        'DB_DATABASE' => (string) ($config['database'] ?? ''),
        'DB_USERNAME' => (string) ($config['username'] ?? ''),
        'DB_PASSWORD' => (string) ($config['password'] ?? ''),
        'DB_URL' => '',
    ];
}

/**
 * @param  list<list<string>>  $commands
 * @return list<array{exit_code: int, ok: bool, exception: ?string, transaction_id: ?string, stderr: string}>
 */
function runConcurrentWorkerCommands(array $commands): array
{
    $env = databaseEnvForWorkers();
    $php = PHP_BINARY;

    /** @var ProcessFactory $processes */
    $processes = app(ProcessFactory::class);

    $results = $processes->concurrently(function (Pool $pool) use ($commands, $env, $php): void {
        foreach ($commands as $index => $command) {
            $pool->as((string) $index)
                ->timeout(30)
                ->env($env)
                ->path(base_path())
                ->command(array_merge([$php], $command));
        }
    });

    $parsed = [];

    foreach ($commands as $index => $command) {
        $result = $results[(string) $index];
        $payload = json_decode($result->output(), true);

        $parsed[] = [
            'exit_code' => $result->exitCode() ?? 1,
            'ok' => (bool) ($payload['ok'] ?? false),
            'exception' => is_array($payload) ? ($payload['exception'] ?? null) : null,
            'transaction_id' => is_array($payload) ? ($payload['transaction_id'] ?? null) : null,
            'stderr' => $result->errorOutput(),
        ];
    }

    return $parsed;
}

/**
 * @param  list<array{from: string, to: string, amount: int}>  $jobs
 * @return list<array{exit_code: int, ok: bool, exception: ?string, transaction_id: ?string, stderr: string}>
 */
function runConcurrentTransfers(array $jobs): array
{
    $worker = base_path('tests/bin/transfer_worker.php');

    return runConcurrentWorkerCommands(array_map(
        fn (array $job): array => [
            $worker,
            $job['from'],
            $job['to'],
            (string) $job['amount'],
        ],
        $jobs,
    ));
}

/**
 * @param  list<array{wallet: string, amount: int, operation: 'deposit'|'withdraw'}>  $jobs
 * @return list<array{exit_code: int, ok: bool, exception: ?string, transaction_id: ?string, stderr: string}>
 */
function runConcurrentFunding(array $jobs): array
{
    $worker = base_path('tests/bin/funding_worker.php');

    return runConcurrentWorkerCommands(array_map(
        fn (array $job): array => [
            $worker,
            $job['wallet'],
            (string) $job['amount'],
            $job['operation'],
        ],
        $jobs,
    ));
}
