<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Property 42: Search Occupancy Filter
 *
 * For any search query, the results should only include sections where current occupancy is less than 90%.
 *
 * Property 43: Search Result Ordering
 *
 * For any search results, they should be sorted by occupancy percentage in ascending order (lowest occupancy first).
 *
 * **Validates: Requirements 16.4, 16.5**
 */
beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->convention = Convention::factory()->create();

    $this->convention->users()->attach($this->owner->id);
    DB::table('convention_user_roles')->insert([
        'convention_id' => $this->convention->id,
        'user_id' => $this->owner->id,
        'role' => 'Owner',
        'created_at' => now(),
    ]);

    $this->floor = Floor::factory()->create([
        'convention_id' => $this->convention->id,
    ]);
});

it('excludes sections with occupancy >= 90% from search results', function () {
    // Property 42: sections with occupancy >= 90 must never appear
    $below = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'occupancy' => 50,
        'number_of_seats' => 100,
    ]);

    $atBoundary = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'occupancy' => 90,
        'number_of_seats' => 100,
    ]);

    $above = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'occupancy' => 100,
        'number_of_seats' => 100,
    ]);

    actingAs($this->owner);

    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();

    $sectionIds = collect($response->original->getData()['page']['props']['sections']['data'])
        ->pluck('id')
        ->all();

    expect($sectionIds)->toContain($below->id);
    expect($sectionIds)->not->toContain($atBoundary->id);
    expect($sectionIds)->not->toContain($above->id);
});

it('filters out sections with occupancy >= 90% across random occupancy values', function () {
    // Property 42 (property-based): generate random occupancy values and verify the filter
    for ($iteration = 0; $iteration < 15; $iteration++) {
        // Clean slate for each iteration
        Section::where('floor_id', $this->floor->id)->delete();

        $sectionMap = [];
        $sectionCount = rand(5, 15);

        for ($i = 0; $i < $sectionCount; $i++) {
            $occupancy = rand(0, 100);
            $section = Section::factory()->create([
                'floor_id' => $this->floor->id,
                'occupancy' => $occupancy,
                'number_of_seats' => 200,
                'name' => "Iter{$iteration}-Section{$i}",
            ]);
            $sectionMap[$section->id] = $occupancy;
        }

        actingAs($this->owner);

        $response = get(route('search.index', ['convention' => $this->convention->id]));
        $response->assertOk();

        $results = collect($response->original->getData()['page']['props']['sections']['data']);

        // Property: every returned result must have occupancy < 90
        foreach ($results as $result) {
            expect($result['occupancy'])->toBeLessThan(90,
                "Iteration {$iteration}: Section with occupancy {$result['occupancy']}% should not appear in results"
            );
        }

        // Property: no section with occupancy >= 90 should appear
        $resultIds = $results->pluck('id')->all();
        foreach ($sectionMap as $id => $occupancy) {
            if ($occupancy >= 90) {
                expect($resultIds)->not->toContain($id,
                    "Iteration {$iteration}: Section with occupancy {$occupancy}% must be excluded"
                );
            }
        }

        // Property: count of results should match sections with occupancy < 90
        $expectedCount = count(array_filter($sectionMap, fn ($occ) => $occ < 90));
        expect($results->count())->toBe($expectedCount,
            "Iteration {$iteration}: Expected {$expectedCount} results, got {$results->count()}"
        );
    }
});

it('returns search results sorted by occupancy ascending', function () {
    // Property 43: results must be ordered by occupancy asc
    $occupancies = [75, 10, 50, 0, 89, 25, 60];

    foreach ($occupancies as $i => $occupancy) {
        Section::factory()->create([
            'floor_id' => $this->floor->id,
            'occupancy' => $occupancy,
            'number_of_seats' => 100,
            'name' => "Sort-Section-{$i}",
        ]);
    }

    actingAs($this->owner);

    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();

    $results = collect($response->original->getData()['page']['props']['sections']['data']);
    $returnedOccupancies = $results->pluck('occupancy')->all();

    // All returned should be < 90
    foreach ($returnedOccupancies as $occ) {
        expect($occ)->toBeLessThan(90);
    }

    // Verify ascending order
    for ($i = 1; $i < count($returnedOccupancies); $i++) {
        expect($returnedOccupancies[$i])->toBeGreaterThanOrEqual(
            $returnedOccupancies[$i - 1],
            "Results should be sorted ascending: {$returnedOccupancies[$i]} should be >= {$returnedOccupancies[$i - 1]}"
        );
    }
});

it('maintains ascending occupancy order across random data sets', function () {
    // Property 43 (property-based): verify ordering holds for random inputs
    for ($iteration = 0; $iteration < 15; $iteration++) {
        // Clean slate for each iteration
        Section::where('floor_id', $this->floor->id)->delete();

        $sectionCount = rand(6, 15);
        for ($i = 0; $i < $sectionCount; $i++) {
            Section::factory()->create([
                'floor_id' => $this->floor->id,
                'occupancy' => rand(0, 100),
                'number_of_seats' => 150,
                'name' => "Order-Iter{$iteration}-S{$i}",
            ]);
        }

        actingAs($this->owner);

        $response = get(route('search.index', ['convention' => $this->convention->id]));
        $response->assertOk();

        $results = collect($response->original->getData()['page']['props']['sections']['data']);
        $returnedOccupancies = $results->pluck('occupancy')->all();

        // Verify ascending order
        for ($i = 1; $i < count($returnedOccupancies); $i++) {
            expect($returnedOccupancies[$i])->toBeGreaterThanOrEqual(
                $returnedOccupancies[$i - 1],
                "Iteration {$iteration}: occupancy {$returnedOccupancies[$i]} should be >= {$returnedOccupancies[$i - 1]}"
            );
        }
    }
});
