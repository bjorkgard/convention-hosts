<?php

namespace App\Http\Middleware;

use App\Support\Consent\OptionalStorageRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $optionalStorage = app(OptionalStorageRegistry::class);

        View::share('appearance', $optionalStorage->trustedAppearance($request));
        View::share('theme', $optionalStorage->trustedTheme($request));

        return $next($request);
    }
}
