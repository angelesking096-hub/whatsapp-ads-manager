<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\URL;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->booted(function () {
        if (app()->environment('production') || str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }
    })
    ->create();
