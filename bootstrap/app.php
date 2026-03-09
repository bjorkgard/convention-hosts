<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecureHeaders;
use App\Listeners\SecurityEventListener;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'theme']);

        $middleware->web(append: [
            SecureHeaders::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (InvalidSignatureException $e, $request) {
            // Determine if the signature is expired or simply invalid
            $hasSignature = $request->has('signature');
            $reason = $hasSignature ? 'expired' : 'invalid';

            SecurityEventListener::logInvalidSignature($reason);

            // Use guest-specific error page for guest verification routes
            $routeName = $request->route()?->getName() ?? '';
            $page = str_starts_with($routeName, 'guest-verification.')
                ? 'auth/guest-convention-invalid'
                : 'auth/invitation-invalid';

            return Inertia::render($page, [
                'reason' => $reason,
            ])->toResponse($request)->setStatusCode(403);
        });
    })->create();
