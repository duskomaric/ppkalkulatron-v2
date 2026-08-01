<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

/*
 * NativePHP izostavlja cijeli storage/framework iz paketa, a config/view.php
 * traži realpath() te putanje — bez nje `artisan package:discover` pada usred
 * pakovanja sa „Please provide a valid cache path". Zato se stvara ovdje, prije
 * nego što se aplikacija uopšte podigne.
 */
foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $directory) {
    $path = dirname(__DIR__).'/storage/'.$directory;

    if (! is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JSON akcije (npr. slanje e-maila) dobijaju JSON greške, dok standardne
        // Laravel forme dobijaju redirect nazad sa porukama u sesiji.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson(),
        );
    })->create();
