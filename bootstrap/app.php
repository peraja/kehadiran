<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Polyfill for iconv if ext-iconv is not enabled in PHP environment (e.g. cPanel)
if (!function_exists('iconv')) {
    function iconv(string $from_encoding, string $to_encoding, string $string): string|false
    {
        $to_encoding = preg_replace('/(\/\/TRANSLIT|\/\/IGNORE)$/i', '', $to_encoding);
        $from_encoding = preg_replace('/(\/\/TRANSLIT|\/\/IGNORE)$/i', '', $from_encoding);

        if (function_exists('mb_convert_encoding')) {
            try {
                return mb_convert_encoding($string, $to_encoding, $from_encoding);
            } catch (\Throwable) {
                return false;
            }
        }

        return $string;
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
