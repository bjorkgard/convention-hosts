<?php

namespace App\Http\Controllers;

use App\Actions\UpdateOccupancyAction;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateOccupancyRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    /**
     * Display a listing of sections for the floor, scoped by role.
     */
    public function index(Request $request, Convention $convention, Floor $floor): Response
    {
        $query = $floor->sections()->with('floor');

        // Apply role-based scoping from ScopeByRole middleware
        if ($scopedSectionIds = $request->get('scoped_section_ids')) {
            $query->whereIn('id', $scopedSectionIds);
        }

        $sections = $query->get();

        $userRoles = $request->user()->rolesForConvention($convention);

        return Inertia::render('sections/index', [
            'convention' => $convention,
            'floor' => $floor,
            'sections' => $sections,
            'userRoles' => $userRoles,
        ]);
    }

    /**
     * Display the section detail with occupancy controls.
     */
    public function show(Request $request, Section $section): Response
    {
        $section->load(['floor.convention', 'lastUpdatedBy']);

        $convention = $section->floor->convention;
        $userRoles = $request->user()->rolesForConvention($convention);

        // Find active (unlocked) attendance period for this convention
        $activePeriod = $convention->attendancePeriods()
            ->active()
            ->latest('created_at')
            ->first();

        $myReport = $activePeriod
            ? $activePeriod->reports()->where('section_id', $section->id)->where('reported_by', $request->user()->id)->first()
            : null;

        return Inertia::render('sections/show', [
            'section' => $section,
            'floor' => $section->floor,
            'convention' => $convention,
            'userRoles' => $userRoles,
            'activePeriod' => $activePeriod,
            'myReport' => $myReport,
        ]);
    }

    /**
     * Store a newly created section for the floor.
     *
     * When floor_id is present in the request body (from FloorsIndex modal),
     * resolve the Floor model from it; otherwise use the route-bound $floor.
     */
    public function store(StoreSectionRequest $request, Convention $convention, Floor $floor): RedirectResponse
    {
        $validated = $request->validated();

        // When creating from FloorsIndex page, floor_id comes from the request body
        if (isset($validated['floor_id'])) {
            $floor = Floor::findOrFail($validated['floor_id']);
        }

        $this->authorize('create', [Section::class, $floor]);

        $validated['available_seats'] = $validated['number_of_seats'];

        $floor->sections()->create($validated);

        return redirect()->route('floors.index', $convention);
    }

    /**
     * Update the specified section's attributes.
     */
    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('update', $section);

        $section->update($request->validated());

        return redirect()->route('floors.index', $section->floor->convention);
    }

    /**
     * Update the section's occupancy percentage or available seats.
     */
    public function updateOccupancy(UpdateOccupancyRequest $request, Section $section, UpdateOccupancyAction $action): RedirectResponse
    {
        $this->authorize('update', $section);

        $action->execute($section, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Occupancy updated.');
    }

    /**
     * Set the section's occupancy to 100% immediately (FULL button).
     */
    public function setFull(Request $request, Section $section, UpdateOccupancyAction $action): RedirectResponse
    {
        $this->authorize('update', $section);

        $action->execute($section, ['occupancy' => 100], $request->user());

        return redirect()->back()->with('success', 'Section marked as full.');
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section): RedirectResponse
    {
        $this->authorize('delete', $section);

        $convention = $section->floor->convention;

        $section->delete();

        return redirect()->route('floors.index', $convention);
    }
}
