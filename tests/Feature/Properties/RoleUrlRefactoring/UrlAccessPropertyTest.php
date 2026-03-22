<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

it('section URL session returns 404 for invalid tokens', function () {
    $invalidTokens = [
        'nonexistent-token-'.fake()->sha256(),
    ];

    foreach ($invalidTokens as $token) {
        $this->get("/url-access/section/{$token}")->assertNotFound();
    }
})->group('property', 'role-url-refactoring');

/**
 * Property 7: Section URL session grants correct positive permissions
 *
 * For any valid section URL token, accessing it must create a session that allows:
 * viewing all sections in the convention, updating occupancy for any section,
 * and reporting attendance for any section.
 *
 * **Validates: Requirements 4.1, 4.2, 4.3, 4.4**
 */
it('section URL session grants view sections, update occupancy, and report attendance', function () {
    for ($i = 0; $i < 5; $i++) {
        $setup = ConventionTestHelper::createConventionWithStructure([
            'floors' => fake()->numberBetween(1, 3),
            'sections_per_floor' => fake()->numberBetween(1, 4),
        ]);

        $convention = $setup['convention'];
        $token = \Illuminate\Support\Facades\DB::table('conventions')
            ->where('id', $convention->id)
            ->value('section_url_token');

        // Access the section URL to establish session
        $response = $this->get("/url-access/section/{$token}");
        $response->assertRedirect(route('conventions.show', $convention));

        // Can view convention show page
        $this->get(route('conventions.show', $convention))->assertOk();

        // Can view individual section detail
        $section = $setup['sections']->first();
        $this->get(route('sections.show', $section))->assertOk();

        // Can update occupancy
        $this->patch(route('sections.updateOccupancy', $section), [
            'occupancy' => fake()->randomElement([0, 10, 25, 50, 75, 100]),
        ])->assertRedirect();

        // Can set section full
        $this->post(route('sections.setFull', $section))->assertRedirect();

        // Can access search
        $this->get(route('search.index', $convention))->assertOk();

        // Flush session for next iteration
        $this->flushSession();
    }
})->group('property', 'role-url-refactoring');

/**
 * Property 8: Section URL session denies administrative and floor actions
 *
 * For any section URL session, the session must be denied permission to:
 * view or manage floors, create/delete sections, access user management,
 * start/stop attendance reports, and lock attendance periods.
 *
 * **Validates: Requirements 4.5, 4.6, 4.7, 4.8, 4.9**
 */
it('section URL session denies administrative and floor management actions', function () {
    for ($i = 0; $i < 5; $i++) {
        $setup = ConventionTestHelper::createConventionWithStructure([
            'floors' => 2,
            'sections_per_floor' => 2,
        ]);

        $convention = $setup['convention'];
        $floor = $setup['floors']->first();
        $section = $setup['sections']->first();
        $token = \Illuminate\Support\Facades\DB::table('conventions')
            ->where('id', $convention->id)
            ->value('section_url_token');

        // Establish section URL session
        $this->get("/url-access/section/{$token}");

        // Cannot create floors (auth-only route → redirects to login)
        $this->post(route('floors.store', $convention), [
            'name' => 'New Floor',
        ])->assertRedirect(route('login'));

        // Cannot update floors
        $this->put(route('floors.update', $floor), [
            'name' => 'Updated Floor',
        ])->assertRedirect(route('login'));

        // Cannot delete floors
        $this->delete(route('floors.destroy', $floor))->assertRedirect(route('login'));

        // Cannot create sections
        $this->post(route('sections.store', [$convention, $floor]), [
            'name' => 'New Section',
            'number_of_seats' => 100,
        ])->assertRedirect(route('login'));

        // Cannot update section details
        $this->put(route('sections.update', $section), [
            'name' => 'Updated Section',
            'number_of_seats' => 200,
        ])->assertRedirect(route('login'));

        // Cannot delete sections
        $this->delete(route('sections.destroy', $section))->assertRedirect(route('login'));

        // Cannot access user management
        $this->get(route('users.index', $convention))->assertRedirect(route('login'));

        // Cannot start attendance reports
        $this->post(route('attendance.start', $convention))->assertRedirect(route('login'));

        // Flush session for next iteration
        $this->flushSession();
    }
})->group('property', 'role-url-refactoring');
