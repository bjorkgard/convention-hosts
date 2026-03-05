<?php

namespace App\Http\Middleware;

use App\Models\Convention;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeByRole
{
    /**
     * Handle an incoming request.
     *
     * Filters query results based on user's role scope:
     * - Owner and ConventionUser: See everything (no scoping)
     * - FloorUser: Add scoped_floor_ids to request
     * - SectionUser: Add scoped_section_ids to request
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

        // Owner and ConventionUser see everything - no scoping needed
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return $next($request);
        }

        // FloorUser sees only assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            $floorIds = $user->floors()
                ->whereHas('convention', fn($query) => $query->where('id', $convention->id))
                ->pluck('floors.id')
                ->toArray();
            
            $request->merge(['scoped_floor_ids' => $floorIds]);
        }

        // SectionUser sees only assigned sections
        if ($user->hasRole($convention, 'SectionUser')) {
            $sectionIds = $user->sections()
                ->whereHas('floor.convention', fn($query) => $query->where('id', $convention->id))
                ->pluck('sections.id')
                ->toArray();
            
            $request->merge(['scoped_section_ids' => $sectionIds]);
        }

        return $next($request);
    }
}
