<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Property 41: Search Accessibility
 *
 * For any authenticated user regardless of role, the Search page should be accessible.
 *
 * Property 44: Search Role-Agnostic Results
 *
 * For any search query, the results should include all matching sections regardless of the
 * user's role (no role-based filtering applied).
 *
 * **Validates: Requirements 16.1, 16.8**
 */
beforeEach(function () {
    $this->convention = Convention::factory()->create();

    $this->floor = Floor::factory()->create([
        'convention_id' => $this->convention->id,
    ]);

    // Create sections with varying occupancy (all below 90% so they appear in results)
    $this->sections = collect();
    foreach ([0, 20, 40, 60, 80] as $i => $occupancy) {
        $this->sections->push(Section::factory()->create([
            'floor_id' => $this->floor->id,
            'occupancy' => $occupancy,
            'number_of_seats' => 100,
            'name' => "Section-{$i}",
        ]));
    }
});

// --- Property 41: Search Accessibility ---

it('allows Owner to access the search page', function () {
    $owner = ConventionTestHelper::createUserWithRole($this->convention, 'Owner');

    actingAs($owner);
    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();
});

it('allows Administrator to access the search page', function () {
    $administrator = ConventionTestHelper::createUserWithRole($this->convention, 'Administrator');

    actingAs($administrator);
    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();
});

it('grants search access to all role types across random conventions', function () {
    // Property 41 (property-based): verify all roles can access search across multiple iterations
    $roles = ['Owner', 'Administrator'];

    for ($iteration = 0; $iteration < 3; $iteration++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        Section::factory()->create([
            'floor_id' => $floor->id,
            'occupancy' => rand(0, 89),
            'number_of_seats' => 100,
        ]);

        foreach ($roles as $role) {
            $user = ConventionTestHelper::createUserWithRole($convention, $role);

            actingAs($user);
            $response = get(route('search.index', ['convention' => $convention->id]));
            $response->assertOk();
        }
    }
});

// --- Property 44: Search Role-Agnostic Results ---

it('returns identical search results regardless of user role', function () {
    $owner = ConventionTestHelper::createUserWithRole($this->convention, 'Owner');
    $administrator = ConventionTestHelper::createUserWithRole($this->convention, 'Administrator');

    $resultsByRole = [];

    foreach (['Owner' => $owner, 'Administrator' => $administrator] as $role => $user) {
        actingAs($user);
        $response = get(route('search.index', ['convention' => $this->convention->id]));
        $response->assertOk();

        $sectionIds = collect($response->original->getData()['page']['props']['sections']['data'])
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $resultsByRole[$role] = $sectionIds;
    }

    // Both roles should see the exact same sections
    expect($resultsByRole['Administrator'])->toBe($resultsByRole['Owner'], 'Administrator should see same results as Owner');
});

it('returns role-agnostic results across random data sets', function () {
    // Property 44 (property-based): verify no role-based filtering across multiple iterations
    $roles = ['Owner', 'Administrator'];

    for ($iteration = 0; $iteration < 3; $iteration++) {
        $convention = Convention::factory()->create();

        // Create multiple floors with sections
        $floorCount = rand(2, 4);
        $floors = Floor::factory()->count($floorCount)->create(['convention_id' => $convention->id]);

        foreach ($floors as $floor) {
            $sectionCount = rand(2, 5);
            for ($s = 0; $s < $sectionCount; $s++) {
                Section::factory()->create([
                    'floor_id' => $floor->id,
                    'occupancy' => rand(0, 100),
                    'number_of_seats' => rand(50, 200),
                    'name' => "Iter{$iteration}-F{$floor->id}-S{$s}",
                ]);
            }
        }

        $users = [];
        foreach ($roles as $role) {
            $users[$role] = ConventionTestHelper::createUserWithRole($convention, $role);
        }

        $resultsByRole = [];
        foreach ($users as $role => $user) {
            actingAs($user);
            $response = get(route('search.index', ['convention' => $convention->id]));
            $response->assertOk();

            $sectionIds = collect($response->original->getData()['page']['props']['sections']['data'])
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            $resultsByRole[$role] = $sectionIds;
        }

        // All roles must see the same results
        $baseline = $resultsByRole['Owner'];
        foreach ($roles as $role) {
            expect($resultsByRole[$role])->toBe(
                $baseline,
                "Iteration {$iteration}: {$role} should see same search results as Owner"
            );
        }
    }
});
