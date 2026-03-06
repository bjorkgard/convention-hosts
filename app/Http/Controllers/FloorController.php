<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFloorRequest;
use App\Models\Convention;
use App\Models\Floor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FloorController extends Controller
{
    /**
     * Display a listing of floors for the convention, scoped by role.
     */
    public function index(Request $request, Convention $convention): Response
    {
        $query = $convention->floors()->with(['sections.users:id,first_name,last_name', 'users:id,first_name,last_name']);

        // Apply role-based scoping from ScopeByRole middleware
        if ($scopedFloorIds = $request->get('scoped_floor_ids')) {
            $query->whereIn('id', $scopedFloorIds);
        }

        if ($scopedSectionIds = $request->get('scoped_section_ids')) {
            $query->whereHas('sections', function ($q) use ($scopedSectionIds) {
                $q->whereIn('id', $scopedSectionIds);
            });
            $query->with(['sections' => function ($q) use ($scopedSectionIds) {
                $q->whereIn('id', $scopedSectionIds)->with('users:id,first_name,last_name');
            }]);
        }

        $floors = $query->get();

        $userRoles = $request->user()->rolesForConvention($convention);
        $userFloorIds = $request->user()->floors()->pluck('floors.id')->toArray();
        $userSectionIds = $request->user()->sections()->pluck('sections.id')->toArray();

        return Inertia::render('floors/index', [
            'convention' => $convention,
            'floors' => $floors,
            'userRoles' => $userRoles,
            'userFloorIds' => $userFloorIds,
            'userSectionIds' => $userSectionIds,
        ]);
    }

    /**
     * Store a newly created floor for the convention.
     *
     * Only Owner and ConventionUser can add floors (FloorUser cannot).
     */
    public function store(StoreFloorRequest $request, Convention $convention): RedirectResponse
    {
        $this->authorize('create', [Floor::class, $convention]);

        $convention->floors()->create($request->validated());

        return redirect()->route('conventions.show', $convention);
    }

    /**
     * Update the specified floor's name.
     *
     * FloorUser can edit assigned floors. Uses FloorPolicy authorization.
     */
    public function update(StoreFloorRequest $request, Floor $floor): RedirectResponse
    {
        $this->authorize('update', $floor);

        $floor->update($request->validated());

        return redirect()->route('conventions.show', $floor->convention);
    }

    /**
     * Remove the specified floor (cascades to sections via DB).
     *
     * Only Owner and ConventionUser can delete floors.
     */
    public function destroy(Floor $floor): RedirectResponse
    {
        $this->authorize('delete', $floor);

        $convention = $floor->convention;

        $floor->delete();

        return redirect()->route('conventions.show', $convention);
    }
}
