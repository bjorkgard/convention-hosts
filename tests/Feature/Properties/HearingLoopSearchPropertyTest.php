<?php

// Feature: hearing-loop-section, Property 3: Search filter returns only hearing_loop sections

use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Property 3: Search filter returns only hearing_loop sections
 *
 * For any set of sections with mixed hearing_loop values, when the search is performed
 * with the hearing_loop filter active, every section in the result set must have
 * hearing_loop = true, and the response filters object must include the hearing_loop
 * filter value.
 *
 * **Validates: Requirements 5.5, 5.6**
 */
beforeEach(function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 0,
        'with_owner' => true,
    ]);

    $this->convention = $structure['convention'];
    $this->floor = $structure['floors']->first();
    $this->owner = $structure['owner'];
});

it('returns only hearing_loop sections when filter is active', function () {
    // Create a mix of hearing_loop true/false sections, all with low occupancy
    $withLoop = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'hearing_loop' => true,
        'occupancy' => 20,
        'number_of_seats' => 100,
    ]);

    $withoutLoop = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'hearing_loop' => false,
        'occupancy' => 30,
        'number_of_seats' => 100,
    ]);

    actingAs($this->owner);

    $response = get(route('search.index', [
        'convention' => $this->convention->id,
        'hearing_loop' => '1',
    ]));

    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $results = collect($props['sections']['data']);

    // Every result must have hearing_loop = true
    expect($results)->not->toBeEmpty();
    foreach ($results as $section) {
        expect($section['hearing_loop'])->toBeTrue();
    }

    // The section without hearing_loop must not appear
    $resultIds = $results->pluck('id')->all();
    expect($resultIds)->toContain($withLoop->id);
    expect($resultIds)->not->toContain($withoutLoop->id);

    // Filters object must include hearing_loop
    expect($props['filters'])->toHaveKey('hearing_loop');
});

it('filters to only hearing_loop sections across random data sets', function () {
    for ($iteration = 0; $iteration < 100; $iteration++) {
        // Clean slate for each iteration
        Section::where('floor_id', $this->floor->id)->delete();

        $sectionCount = rand(4, 10);
        $createdSections = [];

        for ($i = 0; $i < $sectionCount; $i++) {
            $hasLoop = (bool) rand(0, 1);
            $occupancy = rand(0, 89); // All below 90% so they appear in available results

            $section = Section::factory()->create([
                'floor_id' => $this->floor->id,
                'hearing_loop' => $hasLoop,
                'occupancy' => $occupancy,
                'number_of_seats' => 100,
                'name' => "Iter{$iteration}-S{$i}",
            ]);

            $createdSections[] = [
                'id' => $section->id,
                'hearing_loop' => $hasLoop,
            ];
        }

        actingAs($this->owner);

        $response = get(route('search.index', [
            'convention' => $this->convention->id,
            'hearing_loop' => '1',
        ]));

        $response->assertOk();

        $props = $response->original->getData()['page']['props'];
        $results = collect($props['sections']['data']);

        // Property: every returned section must have hearing_loop = true
        foreach ($results as $section) {
            expect($section['hearing_loop'])->toBeTrue(
                "Iteration {$iteration}: Section {$section['name']} should have hearing_loop=true"
            );
        }

        // Property: result count must match the number of hearing_loop=true sections created
        $expectedCount = count(array_filter($createdSections, fn ($s) => $s['hearing_loop']));
        expect($results->count())->toBe(
            $expectedCount,
            "Iteration {$iteration}: Expected {$expectedCount} hearing_loop sections, got {$results->count()}"
        );

        // Property: filters object must include hearing_loop value
        expect($props['filters'])->toHaveKey('hearing_loop');
    }
});
