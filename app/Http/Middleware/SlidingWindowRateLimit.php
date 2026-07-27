<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class SlidingWindowRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 3, int $windowSeconds = 60): Response
    {
        $key = $this->resolveKey($request);
        $now = microtime(true);
        $windowStart = $now - $windowSeconds;
        $windowStartString = (string) $windowStart;

        Redis::zremrangebyscore($key, '0', $windowStartString); // deletes every member whose score is less than or equal to the window start time

        $attempts = Redis::zcard($key); // count the members left in the sorted set after removing the old ones

        if ($attempts >= $maxAttempts) {
            $oldestInWindow = Redis::zrange($key, 0, 0, ['withscores' => true]); // fetches the oldest member and its score. 0, 0 means fetch the first one
            $retryAfter = $oldestInWindow
                ? (int) ceil($windowSeconds - ($now - (float) array_values($oldestInWindow)[0]))
                : $windowSeconds;

            $response = response()->json([
                'message' => 'Too many login attempts. Please try again shortly.',
            ], 429);

            $response->headers->set('Retry-After', (string) max($retryAfter, 1));
            $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', '0');

            return $response;
        }

        Redis::zadd($key, $now, (string) $now.'-'.bin2hex(random_bytes(4)));
        Redis::expire($key, $windowSeconds);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max($maxAttempts - $attempts - 1, 0));

        return $response;
    }

    private function resolveKey(Request $request): string
    {
        // Rate limit by IP and email combo, so one IP can't lock out unrelated accounts,
        // and one attacker can't rotate emails to dodge an IP-only limit.
        $identifier = $request->input('email', 'unknown');

        return 'login_rate_limit:'.$request->ip().':'.sha1($identifier);
    }
}
