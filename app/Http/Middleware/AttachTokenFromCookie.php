<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachTokenFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $request->cookie('access_token');

        if (! $request->hasHeader('Authorization') && is_string($accessToken) && $accessToken !== '') {
            $request->headers->set('Authorization', 'Bearer '.$accessToken);
        }

        return $next($request);
    }
}
