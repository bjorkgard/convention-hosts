<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

/**
 * Helper to create a user with a specific role for the convention.
 */
function createUserWithRole(Convention $convention, string $role, ?Floor $floor = null, ?Section $section = null): User
{
    $user = User::factory()->create();
    $convention->users()->attach($user->id);

    DB::table('convention_user_roles')->insert([
        'convention_id' => $convention->id,
        'user_id' => $user->id,
        'role' => $role,
        'created_at' => now(),
    ]);

    if ($role === 'FloorUser' && $floor) {
        DB::table('floor_user')->insert([
            'floor_id' => $floor->id,
            'user_id' => $user->id,
        ]);
    }

    if ($role === 'SectionUser' && $section) {
        DB::table('section_user')->insert([
            'section_id' => $section->id,
            'user_id' => $user->id,
        ]);
    }

    return $user;
}

// --- Property 41: Search Accessibility ---

it('allows Owner to access the search page', function () {
    $owner = createUserWithRole($this->convention, 'Owner');

    actingAs($owner);
    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();
});

it('allows ConventionUser to access the search page', function () {
    $conventionUser = createUserWithRole($this->convention, 'ConventionUser');

    actingAs($conventionUser);
    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();
});

it('allows FloorUser to access the search page', function () {
    $floorUser = createUserWithRole($this->convention, 'FloorUser', $this->floor);

    actingAs($floorUser);
    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();
});

it('allows SectionUser to access the search page', function () {
    $sectionUser = createUserWithRole($this->convention, 'SectionUser', null, $this->sections->first());

    actingAs($sectionUser);
    $response = get(route('search.index', ['convention' => $this->convention->id]));
    $response->assertOk();
});

it('grants search access to all role types across random conventions', function () {
    // Property 41 (property-based): verify all roles can access search across multiple iterations
    $roles = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];

    for ($iteration = 0; $iteration < 10; $iteration++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        $section = Section::factory()->create([
            'floor_id' => $floor->id,
            'occupancy' => rand(0, 89),
            'number_of_seats' => 100,
        ]);

        foreach ($roles as $role) {
            $user = createUserWithRole(
                $convention,
                $role,
                $role === 'FloorUser' ? $floor : null,
                $role === 'SectionUser' ? $section : null,
            );

            actingAs($user);
            $response = get(route('search.index', ['convention' => $convention->id]));
            $response->assertOk();
        }
    }
});

// --- Property 44: Search Role-Agnostic Results ---

it('returns identical search results regardless of user role', function () {
    // Create one user per role
    $owner = createUserWithRole($this->convention, 'Owner');
    $conventionUser = createUserWithRole($this->convention, 'ConventionUser');
    $floorUser = createUserWithRole($this->convention, 'FloorUser', $this->floor);
    $sectionUser = createUserWithRole($this->convention, 'SectionUser', null, $this->sections->first());

    $resultsByRole = [];

    foreach (['Owner' => $owner, 'ConventionUser' => $conventionUser, 'FloorUser' => $floorUser, 'SectionUser' => $sectionUser] as $role => $user) {
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

    // All roles should see the exact same sections
    $ownerResults = $resultsByRole['Owner'];
    expect($resultsByRole['ConventionUser'])->toBe($ownerResults, 'ConventionUser should see same results as Owner');
    expect($resultsByRole['FloorUser'])->toBe($ownerResults, 'FloorUser should see same results as Owner');
    expect($resultsByRole['SectionUser'])->toBe($ownerResults, 'SectionUser should see same results as Owner');
});

it('returns role-agnostic results across random data sets', function () {
    // Property 44 (property-based): verify no role-based filtering across multiple iterations
    $roles = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];

    for ($iteration = 0; $iteration < 10; $iteration++) {
        $convention = Convention::factory()->create();

        // Create multiple floors with sections
        $floorCount = rand(2, 4);
        $floors = Floor::factory()->count($floorCount)->create(['convention_id' => $convention->id]);
        $allSections = collect();

        foreach ($floors as $floor) {
            $sectionCount = rand(2, 5);
            for ($s = 0; $s < $sectionCount; $s++) {
                $allSections->push(Section::factory()->create([
                    'floor_id' => $floor->id,
                    'occupancy' => rand(0, 100),
                    'number_of_seats' => rand(50, 200),
                    'name' => "Iter{$iteration}-F{$floor->id}-S{$s}",
                ]));
            }
        }

        // Assign FloorUser to only the first floor, SectionUser to only the first section
        $firstFloor = $floors->first();
        $firstSection = $allSections->first();

        $users = [];
        foreach ($roles as $role) {
            $users[$role] = createUserWithRole(
                $convention,
                $role,
                $role === 'FloorUser' ? $firstFloor : null,
                $role === 'SectionUser' ? $firstSection : null,
            );
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
