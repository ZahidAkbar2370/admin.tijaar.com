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
            'admin.web' => \App\Http\Middleware\AdminWebMiddleware::class,
            'admin.permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'log.activity' => \App\Http\Middleware\LogActivityMiddleware::class,
            'email.verified' => \App\Http\Middleware\EnsureApiEmailVerified::class,
        ]);

        $middleware->appendToGroup('api', [
            \App\Http\Middleware\LogActivityMiddleware::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\LogActivityMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
