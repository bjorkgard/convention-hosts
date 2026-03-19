<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Property 47: Navigation Visibility by Role
 *
 * For any user, the navigation links displayed should be scoped based on their role.
 * The convention show page returns `userRoles` in Inertia props, which the frontend
 * NavConvention component uses to determine navigation visibility.
 *
 * With the two-role system (Owner, Administrator), both roles see all navigation items:
 * - Administration (formerly Floors): visible to Owner, Administrator
 * - Sections: visible to Owner, Administrator
 * - Users: visible to Owner, Administrator
 * - Availability (formerly Search): visible to Owner, Administrator
 *
 * **Validates: Requirements 7.1, 7.2, 18.3**
 */
beforeEach(function () {
    $this->convention = Convention::factory()->create();
    $this->floor = Floor::factory()->create(['convention_id' => $this->convention->id]);
    $this->section = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'number_of_seats' => 100,
    ]);
});

// --- Property 47: Navigation Visibility by Role ---

it('returns Owner role in userRoles prop for Owner users', function () {
    $owner = ConventionTestHelper::createUserWithRole($this->convention, 'Owner');

    actingAs($owner);
    $response = get(route('conventions.show', $this->convention));
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $userRoles = collect($props['userRoles'])->values()->all();

    expect($userRoles)->toContain('Owner');
});

it('returns Administrator role in userRoles prop for Administrator users', function () {
    $administrator = ConventionTestHelper::createUserWithRole($this->convention, 'Administrator');

    actingAs($administrator);
    $response = get(route('conventions.show', $this->convention));
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    $userRoles = collect($props['userRoles'])->values()->all();

    expect($userRoles)->toContain('Administrator');
});

it('scopes navigation visibility correctly across all roles in randomized scenarios', function () {
    // Property 47 (property-based): verify navigation visibility rules hold across many iterations
    $roleConfigs = [
        ['role' => 'Owner'],
        ['role' => 'Administrator'],
    ];

    for ($iteration = 0; $iteration < 10; $iteration++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        Section::factory()->create([
            'floor_id' => $floor->id,
            'number_of_seats' => rand(50, 200),
        ]);

        foreach ($roleConfigs as $config) {
            $user = ConventionTestHelper::createUserWithRole($convention, $config['role']);

            actingAs($user);
            $response = get(route('conventions.show', $convention));
            $response->assertOk();

            $props = $response->original->getData()['page']['props'];
            $userRoles = collect($props['userRoles'])->values()->all();

            expect($userRoles)->toContain($config['role']);
        }
    }
});
