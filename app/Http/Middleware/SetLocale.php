<?php

namespace App\Http\Middleware;

use App\Models\Convention;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve and apply the active locale per-request.
     *
     * Priority chain:
     * 1. Authenticated user's locale (if not null)
     * 2. URL session convention's locale
     * 3. Fallback: 'sv'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // Priority 1: Authenticated user preference
        if ($user = $request->user()) {
            $locale = $user->locale;
        }

        // Priority 2: Convention locale from URL session
        if (! $locale) {
            $urlSession = $request->session()->get('url_session');
            if ($urlSession) {
                $convention = Convention::find($urlSession['convention_id']);
                $locale = $convention?->locale;
            }
        }

        // Priority 3: Fallback
        App::setLocale($locale ?? 'sv');

        return $next($request);
    }
}
