<?php

namespace App\Console\Commands;

use App\Http\Middleware\SlidingWindowRateLimit;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class RateLimitProbe extends Command
{
    protected $signature = 'probe:rate-limit {key} {ipMax} {emailMax} {window}';

    protected $description = 'Probe the sliding window rate limiter for concurrency testing.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $key = (string) $this->argument('key');

        $email = $key.'@example.com';
        $ip = $this->keyToFakeIp($key);

        $request = Request::create('/login', 'POST', ['email' => $email]);
        $request->server->set('REMOTE_ADDR', $ip);

        $middleware = app(SlidingWindowRateLimit::class);

        $response = $middleware->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            (int) $this->argument('ipMax'),
            (int) $this->argument('emailMax'),
            (int) $this->argument('window'),
        );

        $this->line($response->getStatusCode() === 429 ? 'BLOCKED' : 'ALLOWED');

        return self::SUCCESS;
    }

    private function keyToFakeIp(string $key): string
    {
        // Deterministically map the test key to a valid-looking IPv4 address,
        // so each unique key gets its own isolated per-IP rate-limit bucket too.
        $hash = crc32($key);

        return sprintf(
            '10.%d.%d.%d',
            ($hash >> 16) & 255,
            ($hash >> 8) & 255,
            $hash & 255,
        );
    }
}
