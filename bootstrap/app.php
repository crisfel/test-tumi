<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use PayIn\Infrastructure\Http\Exceptions\PayInExceptionRenderer;
use PayIn\Infrastructure\Http\Middleware\CorrelationIdMiddleware;
use PayIn\Infrastructure\Http\Middleware\ForceJsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'payin.json' => ForceJsonResponse::class,
        ]);

        $middleware->api(append: [
            CorrelationIdMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn ($request, $exception): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(
            static fn (Throwable $exception) => (new PayInExceptionRenderer())->render($exception),
        );
    })
    ->create();
