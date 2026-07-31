<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
        // Drawer šalje formu preko XHR-a i očekuje greške kao JSON; obična
        // stranica i dalje dobija preusmjerenje nazad sa porukama u sesiji.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson(),
        );

        /*
         * Zastarjelo preusmjerenje „nazad" može pogoditi rutu koja prima samo POST.
         * U pregledniku se to riješi adresnom linijom; u aplikaciji na telefonu je
         * to ćorsokak, pa se takav zahtjev vraća na početak.
         */
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->route('invoices.index');
        });
    })->create();
