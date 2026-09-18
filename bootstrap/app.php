<?php

use App\Http\Middleware\AttachTokenFromCookie;
use App\Http\Middleware\RestrictSwaggerDocumentation;
use App\Http\Middleware\SlidingWindowRateLimit;
use App\Http\Middleware\VerifyXsrfToken;
use App\Models\Account;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AttachTokenFromCookie::class);
        $middleware->appendToGroup('api', VerifyXsrfToken::class);
        $middleware->alias([
            'sliding.throttle' => SlidingWindowRateLimit::class,
            'swagger.access' => RestrictSwaggerDocumentation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // ModelNotFoundException is mapped to NotFoundHttpException before render callbacks run.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $e->getPrevious();
            $message = $previous instanceof ModelNotFoundException && $previous->getModel() === Account::class
                ? 'Account not found.'
                : (str_starts_with($e->getMessage(), 'No query results for model')
                    ? 'Resource not found.'
                    : ($e->getMessage() !== '' ? $e->getMessage() : 'Not found.'));

            return response()->json([
                'message' => $message,
            ], 404);
        });

        // Keep CSRF / other HTTP aborts as message-only (no debug exception/file/trace keys).
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*') || $e->getStatusCode() === 404) {
                return null;
            }

            $message = $e->getMessage() !== ''
                ? $e->getMessage()
                : (Response::$statusTexts[$e->getStatusCode()] ?? 'Error');

            return response()->json([
                'message' => $message,
            ], $e->getStatusCode());
        });
    })->create();
