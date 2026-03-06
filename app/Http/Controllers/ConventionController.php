<?php

namespace App\Http\Controllers;

use App\Actions\CreateConventionAction;
use App\Actions\ExportConventionAction;
use App\Http\Requests\StoreConventionRequest;
use App\Http\Requests\UpdateConventionRequest;
use App\Models\Convention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConventionController extends Controller
{
    /**
     * Display a listing of the user's conventions.
     */
    public function index(Request $request): Response
    {
        $conventions = $request->user()
            ->conventions()
            ->with('floors.sections')
            ->orderBy('start_date', 'desc')
            ->get();

        return Inertia::render('conventions/index', [
            'conventions' => $conventions,
        ]);
    }

    /**
     * Show the form for creating a new convention.
     */
    public function create(): Response
    {
        return Inertia::render('conventions/create');
    }

    /**
     * Store a newly created convention.
     */
    public function store(StoreConventionRequest $request, CreateConventionAction $action): \Illuminate\Http\RedirectResponse
    {
        $convention = $action->execute(
            $request->validated(),
            $request->user()
        );

        return redirect()->route('conventions.show', $convention);
    }

    /**
     * Display the specified convention with role-scoped data.
     */
    public function show(Request $request, Convention $convention): Response
    {
        $user = $request->user();

        // Load floors with sections, scoped by user role
        $floorsQuery = $convention->floors()->with('sections');

        if ($scopedFloorIds = $request->get('scoped_floor_ids')) {
            $floorsQuery->whereIn('id', $scopedFloorIds);
        }

        if ($scopedSectionIds = $request->get('scoped_section_ids')) {
            $floorsQuery->whereHas('sections', function ($query) use ($scopedSectionIds) {
                $query->whereIn('id', $scopedSectionIds);
            });
            $floorsQuery->with(['sections' => function ($query) use ($scopedSectionIds) {
                $query->whereIn('id', $scopedSectionIds);
            }]);
        }

        $floors = $floorsQuery->get();

        // Load attendance periods (locked ones for display)
        $attendancePeriods = $convention->attendancePeriods()
            ->with('reports.section', 'reports.reportedBy')
            ->orderBy('date', 'desc')
            ->orderBy('period', 'desc')
            ->get();

        // Load users with their roles for this convention (single query for all roles)
        $users = $convention->users()->get();
        $allRoles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $users = $users->map(function ($user) use ($allRoles) {
            $user->roles = ($allRoles[$user->id] ?? collect())->pluck('role');

            return $user;
        });

        // Get current user's roles for this convention
        $userRoles = $user->rolesForConvention($convention);
        $userFloorIds = $user->floors()->pluck('floors.id')->toArray();
        $userSectionIds = $user->sections()->pluck('sections.id')->toArray();

        return Inertia::render('conventions/show', [
            'convention' => $convention,
            'floors' => $floors,
            'attendancePeriods' => $attendancePeriods,
            'users' => $users,
            'userRoles' => $userRoles,
            'userFloorIds' => $userFloorIds,
            'userSectionIds' => $userSectionIds,
        ]);
    }

    /**
     * Update the specified convention.
     */
    public function update(UpdateConventionRequest $request, Convention $convention): \Illuminate\Http\RedirectResponse
    {
        $convention->update($request->validated());

        return redirect()->route('conventions.show', $convention);
    }

    /**
     * Remove the specified convention.
     */
    public function destroy(Convention $convention): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $convention);

        $convention->delete();

        return redirect()->route('conventions.index');
    }

    /**
     * Export convention data in the specified format.
     */
    public function export(Request $request, Convention $convention, ExportConventionAction $action): BinaryFileResponse
    {
        $this->authorize('export', $convention);

        $format = $request->input('format', 'xlsx');
        $filePath = $action->execute($convention, $format);

        return response()->download($filePath)->deleteFileAfterSend();
    }
}
