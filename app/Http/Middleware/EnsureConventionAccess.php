<?php

namespace App\Http\Middleware;

use App\Listeners\SecurityEventListener;
use App\Models\Convention;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConventionAccess
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the authenticated user has any role for the convention.
     * Aborts with 403 if the user has no access to the convention.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $convention = $request->route('convention');

        // If convention is not in the route, skip this middleware
        if (! $convention instanceof Convention) {
            return $next($request);
        }

        // Check if user has any role for this convention
        if (! $user->conventions->contains($convention)) {
            SecurityEventListener::logAuthorizationFailure(
                "No access to convention #{$convention->id}",
                $user->id,
            );

            abort(403, 'No access to this convention');
        }

        return $next($request);
    }
}
