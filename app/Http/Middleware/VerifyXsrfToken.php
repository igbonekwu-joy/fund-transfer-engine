<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyXsrfToken
{
    /** @var array<int, string> */
    private array $csrfExempt = [
        'api/v1/auth/login',
        'api/v1/auth/register',
    ];

    /** @var array<int, string> */
    private array $allowedOrigins;

    public function __construct()
    {
        $this->allowedOrigins = config('cors.allowed_origins', []);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $method = $request->method();

        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        if ($this->isCsrfExempt($request)) {
            $this->verifyOrigin($request);

            return $next($request);
        }

        $cookie = $request->cookie('XSRF-TOKEN');
        $header = $request->header('X-XSRF-TOKEN');

        if (! is_string($cookie) || ! is_string($header) || $cookie === '' || $header === '' || ! hash_equals($cookie, $header)) {
            abort(419, 'CSRF token mismatch.');
        }

        return $next($request);
    }

    private function isCsrfExempt(Request $request): bool
    {
        foreach ($this->csrfExempt as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function verifyOrigin(Request $request): void
    {
        $origin = $request->headers->get('Origin') ?? $this->originFromReferer($request);

        if ($origin === null || ! in_array($origin, $this->allowedOrigins, true)) {
            abort(419, 'Invalid request origin.');
        }
    }

    private function originFromReferer(Request $request): ?string
    {
        $referer = $request->headers->get('Referer');

        if ($referer === null) {
            return null;
        }

        $parts = parse_url($referer);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }
}
