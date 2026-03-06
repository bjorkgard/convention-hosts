<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Convention;
use App\Models\Section;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Search available sections within a convention with optional filters.
     *
     * Accessible to all authenticated users with convention access (no role-based filtering).
     * Filters: floor_id, elder_friendly, handicap_friendly
     * Always filters occupancy < 90% and sorts by occupancy ascending.
     */
    public function index(SearchRequest $request, Convention $convention): Response
    {
        $query = Section::query()
            ->whereHas('floor', function ($q) use ($convention) {
                $q->where('convention_id', $convention->id);
            })
            ->available()
            ->with('floor');

        if ($request->filled('floor_id')) {
            $query->where('floor_id', $request->validated('floor_id'));
        }

        if ($request->boolean('elder_friendly')) {
            $query->where('elder_friendly', true);
        }

        if ($request->boolean('handicap_friendly')) {
            $query->where('handicap_friendly', true);
        }

        $sections = $query->orderBy('occupancy', 'asc')->paginate(20);

        $floors = $convention->floors()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('search/index', [
            'convention' => $convention,
            'sections' => $sections,
            'floors' => $floors,
            'filters' => $request->only(['floor_id', 'elder_friendly', 'handicap_friendly']),
        ]);
    }
}
