<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Property 47: Navigation Visibility by Role
 *
 * For any user, the navigation links displayed should be scoped based on their role.
 * The convention show page returns `userRoles` in Inertia props, which the frontend
 * NavConvention component uses to determine navigation visibility:
 *
 * - Floors: visible to Owner, ConventionUser, FloorUser (NOT SectionUser)
 * - Sections: visible to ALL roles
 * - Users: visible to Owner, ConventionUser, FloorUser (NOT SectionUser)
 * - Search: visible to ALL roles
 *
 * **Validates: Requirements 18.3**
 */
beforeEach(function () {
    $this->convention = Convention::factory()->create();
    $this->floor = Floor::factory()->create(['convention_id' => $this->convention->id]);
    $this->section = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'number_of_seats' => 100,
    ]);
});

/**
 * Helper to create a user with a specific role for the convention.
 */
function createNavUser(Convention $convention, string $role, ?Floor $floor = null, ?Section $section = null): User
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
        DB::table('floor_user')->insertOrIgnore([
            'floor_id' => $floor->id,
            'user_id' => $user->id,
        ]);
    }

    if ($role === 'SectionUser' && $section) {
        DB::table('section_user')->insertOrIgnore([
            'section_id' => $section->id,
            'user_id' => $user->id,
        ]);
    }

    return $user;
}

/**
 * Helper to determine which nav items should be visible for a given set of roles.
 * Returns an array of nav item names that should be visible.
 */
function expectedNavItems(array $roles): array
{
    $canSeeFloors = array_intersect($roles, ['Owner', 'ConventionUser', 'FloorUser']) !== [];
    $canSeeUsers = array_intersect($roles, ['Owner', 'ConventionUser', 'FloorUser']) !== [];

    $items = [];

    if ($canSeeFloors) {
        $items[] = 'Floors';
    }

    // Sections: visible to ALL roles
    $items[] = 'Sections';

    if ($canSeeUsers) {
        $items[] = 'Users';
    }

    // Search: visible to ALL roles
    $items[] = 'Search';

    return $items;
}

// --- Property 47: Navigation Visibility by Role ---

it('returns Owner role in userRoles prop for Owner users', function () {
    $owner = createNavUser($this->convention, 'Owner');

    actingAs($owner);
    $response = get(route('conventions.show', $this->convention));
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $userRoles = collect($props['userRoles'])->values()->all();

    expect($userRoles)->toContain('Owner');
    // Owner should see: Floors, Sections, Users, Search
    expect(expectedNavItems($userRoles))->toBe(['Floors', 'Sections', 'Users', 'Search']);
});

it('returns ConventionUser role in userRoles prop for ConventionUser users', function () {
    $conventionUser = createNavUser($this->convention, 'ConventionUser');

    actingAs($conventionUser);
    $response = get(route('conventions.show', $this->convention));
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $userRoles = collect($props['userRoles'])->values()->all();

    expect($userRoles)->toContain('ConventionUser');
    // ConventionUser should see: Floors, Sections, Users, Search
    expect(expectedNavItems($userRoles))->toBe(['Floors', 'Sections', 'Users', 'Search']);
});

it('returns FloorUser role in userRoles prop for FloorUser users', function () {
    $floorUser = createNavUser($this->convention, 'FloorUser', $this->floor);

    actingAs($floorUser);
    $response = get(route('conventions.show', $this->convention));
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $userRoles = collect($props['userRoles'])->values()->all();

    expect($userRoles)->toContain('FloorUser');
    // FloorUser should see: Floors, Sections, Users, Search
    expect(expectedNavItems($userRoles))->toBe(['Floors', 'Sections', 'Users', 'Search']);
});

it('returns SectionUser role in userRoles prop for SectionUser users', function () {
    $sectionUser = createNavUser($this->convention, 'SectionUser', null, $this->section);

    actingAs($sectionUser);
    $response = get(route('conventions.show', $this->convention));
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $userRoles = collect($props['userRoles'])->values()->all();

    expect($userRoles)->toContain('SectionUser');
    // SectionUser should NOT see Floors or Users — only Sections and Search
    expect(expectedNavItems($userRoles))->toBe(['Sections', 'Search']);
});

it('scopes navigation visibility correctly across all roles in randomized scenarios', function () {
    // Property 47 (property-based): verify navigation visibility rules hold across many iterations
    $roleConfigs = [
        ['roles' => ['Owner'], 'expectedNav' => ['Floors', 'Sections', 'Users', 'Search']],
        ['roles' => ['ConventionUser'], 'expectedNav' => ['Floors', 'Sections', 'Users', 'Search']],
        ['roles' => ['FloorUser'], 'expectedNav' => ['Floors', 'Sections', 'Users', 'Search']],
        ['roles' => ['SectionUser'], 'expectedNav' => ['Sections', 'Search']],
    ];

    for ($iteration = 0; $iteration < 10; $iteration++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        $section = Section::factory()->create([
            'floor_id' => $floor->id,
            'number_of_seats' => rand(50, 200),
        ]);

        foreach ($roleConfigs as $config) {
            $role = $config['roles'][0];
            $user = createNavUser(
                $convention,
                $role,
                $role === 'FloorUser' ? $floor : null,
                $role === 'SectionUser' ? $section : null,
            );

            actingAs($user);
            $response = get(route('conventions.show', $convention));
            $response->assertOk();

            $props = $response->original->getData()['page']['props'];
            $userRoles = collect($props['userRoles'])->values()->all();

            expect($userRoles)->toContain($role);
            expect(expectedNavItems($userRoles))->toBe(
                $config['expectedNav'],
                "Iteration {$iteration}: {$role} should see ".implode(', ', $config['expectedNav'])
            );
        }
    }
});

it('returns correct userRoles for users with multiple roles', function () {
    // A user can have multiple roles — navigation should reflect the most permissive set
    $user = User::factory()->create();
    $this->convention->users()->attach($user->id);

    // Assign both FloorUser and SectionUser roles
    DB::table('convention_user_roles')->insert([
        ['convention_id' => $this->convention->id, 'user_id' => $user->id, 'role' => 'FloorUser', 'created_at' => now()],
        ['convention_id' => $this->convention->id, 'user_id' => $user->id, 'role' => 'SectionUser', 'created_at' => now()],
    ]);
    DB::table('floor_user')->insertOrIgnore([
        'floor_id' => $this->floor->id,
        'user_id' => $user->id,
    ]);
    DB::table('section_user')->insertOrIgnore([
        'section_id' => $this->section->id,
        'user_id' => $user->id,
    ]);

    actingAs($user);
    $response = get(route('conventions.show', $this->convention));
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $userRoles = collect($props['userRoles'])->values()->all();

    expect($userRoles)->toContain('FloorUser');
    expect($userRoles)->toContain('SectionUser');
    // FloorUser + SectionUser: FloorUser grants access to Floors and Users
    expect(expectedNavItems($userRoles))->toBe(['Floors', 'Sections', 'Users', 'Search']);
});
