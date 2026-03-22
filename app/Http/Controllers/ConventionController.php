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

        $canCreateConvention = DB::table('convention_user_roles')
            ->where('user_id', $request->user()->id)
            ->where('role', 'Owner')
            ->exists();

        return Inertia::render('conventions/index', [
            'conventions' => $conventions,
            'canCreateConvention' => $canCreateConvention,
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
        $urlSession = session('url_session');

        // Load floors with sections (no role-based scoping — all users see everything)
        $floors = $convention->floors()->with('sections')->get();

        // Load attendance periods (locked ones for display)
        $attendancePeriods = $convention->attendancePeriods()
            ->with('reports.section')
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

        $users = $users->map(function ($u) use ($allRoles) {
            $u->roles = ($allRoles[$u->id] ?? collect())->pluck('role');

            return $u;
        });

        // Get current user's roles (empty for URL sessions)
        $userRoles = $user?->rolesForConvention($convention) ?? collect();

        // Build props
        $props = [
            'convention' => $convention,
            'floors' => $floors,
            'attendancePeriods' => $attendancePeriods,
            'users' => $users,
            'userRoles' => $userRoles,
        ];

        // Expose access URL to Owner/Administrator only
        $isManager = $user && $user->hasAnyRole($convention, ['Owner', 'Administrator']);
        if ($isManager) {
            $props['section_url'] = $convention->sectionAccessUrl();
        }

        return Inertia::render('conventions/show', $props);
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
     * Regenerate the section URL access token for the convention.
     */
    public function regenerateUrlToken(Request $request, Convention $convention): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $convention);

        $convention->regenerateUrlToken();

        return redirect()->back()->with('success', __('convention.show.url_regenerated'));
    }

    /**
     * Update only the convention locale.
     */
    public function updateLocale(Request $request, Convention $convention): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'max:10', function (string $attribute, mixed $value, \Closure $fail) {
                if (! is_dir(lang_path($value))) {
                    $fail(__('validation.locale_not_available'));
                }
            }],
        ]);

        $convention->update($validated);

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
