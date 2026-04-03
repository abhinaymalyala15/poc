<?php

use App\Http\Middleware\ValidateTwilioSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $proxies = env('TRUSTED_PROXIES', '*');
        if (! is_string($proxies) || $proxies === '') {
            $proxies = '*';
        }
        $middleware->trustProxies(at: $proxies);

        $middleware->validateCsrfTokens(except: [
            'incoming-call',
            'process-recording',
        ]);

        $middleware->alias([
            'twilio.signature' => ValidateTwilioSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
