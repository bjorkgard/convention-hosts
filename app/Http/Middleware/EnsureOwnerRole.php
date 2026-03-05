<?php

namespace App\Http\Middleware;

use App\Models\Convention;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerRole
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the authenticated user has Owner role for the convention.
     * Aborts with 403 if the user is not an owner.
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

        // Check if user has Owner role for this convention
        if (! $user->hasRole($convention, 'Owner')) {
            abort(403, 'Owner role required for this action');
        }

        return $next($request);
    }
}
