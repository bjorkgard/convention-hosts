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
        $floors = $convention->floors()->with('sections')->get();

        $userRoles = $request->user()?->rolesForConvention($convention) ?? collect();

        return Inertia::render('floors/index', [
            'convention' => $convention,
            'floors' => $floors,
            'userRoles' => $userRoles,
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
