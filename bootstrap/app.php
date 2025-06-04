<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\OptionalAuthMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PublicOnlyMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'public_only' => PublicOnlyMiddleware::class,
            'auth' => Authenticate::class,
            'admin' => AdminMiddleware::class,
            'verified' => EnsureEmailIsVerified::class,
            'optional.auth' => OptionalAuthMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->api(append: [
            OptionalAuthMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
