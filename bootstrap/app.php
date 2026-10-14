<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\HandleAccessibility;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'font_size', 'dyslexic_font']);

        // The metrics endpoint must stay scrappable during a maintenance
        // window: its maintenance gauge is what tells the difference
        // between a planned `artisan down` and an outage.
        $middleware->preventRequestsDuringMaintenance(except: ['prometheus']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleAccessibility::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AddSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
