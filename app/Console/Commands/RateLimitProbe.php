<?php

namespace App\Console\Commands;

use App\Http\Middleware\SlidingWindowRateLimit;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class RateLimitProbe extends Command
{
    protected $signature = 'probe:rate-limit {key} {max} {window}';

    protected $description = 'Probe the sliding window rate limiter for concurrency testing.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $request = Request::create('/login', 'POST', ['email' => 'race@example.com']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $middleware = app(SlidingWindowRateLimit::class);

        $response = $middleware->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            (int) $this->argument('max'),
            (int) $this->argument('window'),
        );

        $this->line($response->getStatusCode() === 429 ? 'BLOCKED' : 'ALLOWED');

        return self::SUCCESS;
    }
}
