<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'search.metrics' => \App\Http\Middleware\TrackSearchMetrics::class,
        ]);
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                      \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                      \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                      \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // spec: safe-actions-one-submission.md — new middleware only; existing pipeline untouched.
        // Kill-switch: IDEMPOTENCY_ENABLED=false bypasses it entirely (default on).
        if (env('IDEMPOTENCY_ENABLED', true)) {
            $middleware->appendToGroup('web', \App\Http\Middleware\EnsureIdempotentSubmission::class);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
