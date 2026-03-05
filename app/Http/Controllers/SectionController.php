<?php

namespace App\Http\Controllers;

use App\Actions\UpdateOccupancyAction;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateOccupancyRequest;
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
        $query = $floor->sections();

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
            ->where('locked', false)
            ->latest('created_at')
            ->first();

        return Inertia::render('sections/show', [
            'section' => $section,
            'floor' => $section->floor,
            'convention' => $convention,
            'userRoles' => $userRoles,
            'activePeriod' => $activePeriod,
        ]);
    }

    /**
     * Store a newly created section for the floor.
     */
    public function store(StoreSectionRequest $request, Convention $convention, Floor $floor): RedirectResponse
    {
        $this->authorize('create', [Section::class, $floor]);

        $floor->sections()->create($request->validated());

        return redirect()->route('conventions.show', $convention);
    }

    /**
     * Update the specified section's attributes.
     */
    public function update(StoreSectionRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('update', $section);

        $section->update($request->validated());

        return redirect()->route('sections.show', $section);
    }

    /**
     * Update the section's occupancy percentage or available seats.
     */
    public function updateOccupancy(UpdateOccupancyRequest $request, Section $section, UpdateOccupancyAction $action): RedirectResponse
    {
        $this->authorize('update', $section);

        $action->execute($section, $request->validated(), $request->user());

        return redirect()->back();
    }

    /**
     * Set the section's occupancy to 100% immediately (FULL button).
     */
    public function setFull(Request $request, Section $section, UpdateOccupancyAction $action): RedirectResponse
    {
        $this->authorize('update', $section);

        $action->execute($section, ['occupancy' => 100], $request->user());

        return redirect()->back();
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section): RedirectResponse
    {
        $this->authorize('delete', $section);

        $convention = $section->floor->convention;

        $section->delete();

        return redirect()->route('conventions.show', $convention);
    }
}
