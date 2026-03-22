<?php

namespace App\Http\Middleware;

use App\Listeners\SecurityEventListener;
use App\Models\AttendancePeriod;
use App\Models\Convention;
use App\Models\Section;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConventionOrUrlAccess
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the user has access to the convention via either:
     * 1. Authenticated user with a convention role, or
     * 2. Active URL session with matching convention_id.
     *
     * Aborts with 403 if neither condition is met.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $convention = $this->resolveConvention($request);

        // If no convention could be resolved from any route parameter, skip this middleware
        if (! $convention instanceof Convention) {
            return $next($request);
        }

        // Path 1: Authenticated user with a convention role
        $user = $request->user();
        if ($user && $user->conventions->contains($convention)) {
            return $next($request);
        }

        // Path 2: URL session with matching convention_id and valid token
        $urlSession = session('url_session');
        if ($urlSession && ($urlSession['convention_id'] ?? null) === $convention->id) {
            // Verify the stored token still matches (tokens may have been regenerated)
            $storedToken = $urlSession['token'] ?? null;
            $currentToken = $convention->section_url_token;

            if ($storedToken && $storedToken === $currentToken) {
                return $next($request);
            }

            // Token was regenerated — clear the stale session
            session()->forget('url_session');
        }

        // Neither path matched — deny access
        SecurityEventListener::logAuthorizationFailure(
            "No access to convention #{$convention->id}",
            $user?->id,
        );

        abort(403, 'No access to this convention');
    }

    /**
     * Resolve the convention from route parameters.
     *
     * Checks in order: {convention}, {section} (via floor), {attendancePeriod}.
     * This ensures token validation runs even on section-scoped routes
     * that don't include {convention} in the URL.
     */
    private function resolveConvention(Request $request): ?Convention
    {
        $convention = $request->route('convention');
        if ($convention instanceof Convention) {
            return $convention;
        }

        $section = $request->route('section');
        if ($section instanceof Section) {
            return $section->floor?->convention;
        }

        $attendancePeriod = $request->route('attendancePeriod');
        if ($attendancePeriod instanceof AttendancePeriod) {
            return $attendancePeriod->convention;
        }

        return null;
    }
}
