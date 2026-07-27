<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class SlidingWindowRateLimit
{
    private const LUA_SCRIPT = <<<'LUA'
        local ip_key = KEYS[1]
        local email_key = KEYS[2]
        local now = tonumber(ARGV[1])
        local window = tonumber(ARGV[2])
        local ip_max = tonumber(ARGV[3])
        local email_max = tonumber(ARGV[4])
        local member = ARGV[5]

        redis.call('ZREMRANGEBYSCORE', ip_key, '0', tostring(now - window))
        redis.call('ZREMRANGEBYSCORE', email_key, '0', tostring(now - window))

        local ip_count = redis.call('ZCARD', ip_key)
        local email_count = redis.call('ZCARD', email_key)

        if ip_count >= ip_max or email_count >= email_max then
            local ip_oldest = redis.call('ZRANGE', ip_key, 0, 0, 'WITHSCORES')
            local email_oldest = redis.call('ZRANGE', email_key, 0, 0, 'WITHSCORES')

            local oldest_score = now
            if ip_count >= ip_max and ip_oldest[2] then
                oldest_score = tonumber(ip_oldest[2])
            elseif email_oldest[2] then
                oldest_score = tonumber(email_oldest[2])
            end

            return {0, ip_count, email_count, tostring(oldest_score)}
        end

        redis.call('ZADD', ip_key, tostring(now), member)
        redis.call('EXPIRE', ip_key, window)

        redis.call('ZADD', email_key, tostring(now), member)
        redis.call('EXPIRE', email_key, window)

        return {1, ip_count, email_count, '0'}
    LUA;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ipMaxAttempts = 10, int $emailMaxAttempts = 3, int $windowSeconds = 60): Response
    {
        $keys = $this->resolveKey($request);
        $now = microtime(true);
        $member = (string) $now.'-'.bin2hex(random_bytes(4));

        // @phpstan-ignore-next-line arguments.count, argument.type (Laravel's Redis connection wrapper normalizes this call at runtime; the phpredis-shaped stub Larastan checks against doesn't reflect that translation layer)
        [$allowed, $ipAttempts, $emailAttempts, $oldestScore] = Redis::eval(self::LUA_SCRIPT, 2, $keys['ip'], $keys['email'], (string) $now, (string) $windowSeconds, (string) $ipMaxAttempts, (string) $emailMaxAttempts, $member);

        if (! $allowed) {
            $retryAfter = (int) ceil($windowSeconds - ($now - (float) $oldestScore));

            $response = response()->json([
                'message' => 'Too many login attempts. Please try again shortly.',
            ], 429);

            $response->headers->set('Retry-After', (string) max($retryAfter, 1));
            $response->headers->set('X-RateLimit-Limit', (string) $emailMaxAttempts);
            $response->headers->set('X-RateLimit-Remaining', '0');

            return $response;
        }


        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $emailMaxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max($emailMaxAttempts - $emailAttempts - 1, 0));

        return $response;

        // $windowStart = $now - $windowSeconds;
        // $windowStartString = (string) $windowStart;

        // Redis::zremrangebyscore($key, '0', $windowStartString); // deletes every member whose score is less than or equal to the window start time

        // $attempts = Redis::zcard($key); // count the members left in the sorted set after removing the old ones

        // if ($attempts >= $maxAttempts) {
        //     $oldestInWindow = Redis::zrange($key, 0, 0, ['withscores' => true]); // fetches the oldest member and its score. 0, 0 means fetch the first one
        //     $retryAfter = $oldestInWindow
        //         ? (int) ceil($windowSeconds - ($now - (float) array_values($oldestInWindow)[0]))
        //         : $windowSeconds;

        //     $response = response()->json([
        //         'message' => 'Too many login attempts. Please try again shortly.',
        //     ], 429);

        //     $response->headers->set('Retry-After', (string) max($retryAfter, 1));
        //     $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        //     $response->headers->set('X-RateLimit-Remaining', '0');

        //     return $response;
        // }

        // Redis::zadd($key, $now, $member);
        // Redis::expire($key, $windowSeconds);

        // $response = $next($request);
        // $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        // $response->headers->set('X-RateLimit-Remaining', (string) max($maxAttempts - $attempts - 1, 0));

        // return $response;
    }

    private function resolveKey(Request $request): array
    {
        // Rate limit by IP and email combo, so one IP can't lock out unrelated accounts,
        // and one attacker can't rotate emails to dodge an IP-only limit.
        $identifier = $request->input('email', 'unknown');

        return [
            'ip' => 'login_rate_limit:ip:' . $request->ip(),
            'email' => 'login_rate_limit:email:' . sha1($identifier),
        ];
        }
}
