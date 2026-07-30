<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyXsrfToken
{
    /** @var array<int, string> */
    private array $except = [
        'api/v1/auth/login',
        'api/v1/auth/register',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && ! $this->isExcluded($request)) {
            $cookie = $request->cookie('XSRF-TOKEN');
            $header = $request->header('X-XSRF-TOKEN');

            if (! is_string($cookie) || ! is_string($header) || $cookie === '' || $header === '' || ! hash_equals($cookie, $header)) {
                abort(419, 'CSRF token mismatch.');
            }
        }

        return $next($request);
    }

    private function isExcluded(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
