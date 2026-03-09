<?php

// Feature: section-crud-management, Property 7: Section CRUD authorization enforcement
// Validates: Requirements 3.5, 4.6, 5.6

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Section;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\ConventionTestHelper;

beforeEach(function () {
    Mail::fake();
});

/**
 * Helper: build a convention structure and users for each role.
 * Returns an array with convention, floors, sections, and users keyed by role.
 */
function buildAuthorizationScenario(): array
{
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 2,
        'sections_per_floor' => 2,
    ]);

    $convention = $structure['convention'];
    $owner = $structure['owner'];
    $floor1 = $structure['floors'][0];
    $floor2 = $structure['floors'][1];
    $sections = $structure['sections'];

    $conventionUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');

    // FloorUser assigned to floor1 only
    $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
        'floor_ids' => [$floor1->id],
    ]);

    // SectionUser assigned to first section of floor1 only
    $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
        'section_ids' => [$sections[0]->id],
    ]);

    return [
        'convention' => $convention,
        'floor1' => $floor1,
        'floor2' => $floor2,
        'sections' => $sections,
        'owner' => $owner,
        'conventionUser' => $conventionUser,
        'floorUser' => $floorUser,
        'sectionUser' => $sectionUser,
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// Property 7a: Owner and ConventionUser can create sections on any floor
// ──────────────────────────────────────────────────────────────────────────────

it('allows Owner to create sections on any floor across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $floor = fake()->randomElement([$s['floor1'], $s['floor2']]);

        $response = $this->actingAs($s['owner'])
            ->post(route('sections.store', [$s['convention'], $floor]), [
                'name' => fake()->word()." Owner-Create-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
                'elder_friendly' => fake()->boolean(),
                'handicap_friendly' => fake()->boolean(),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: Owner should be able to create section on floor {$floor->id}"
        );
    }
})->group('property', 'section-crud', 'authorization');

it('allows ConventionUser to create sections on any floor across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $floor = fake()->randomElement([$s['floor1'], $s['floor2']]);

        $response = $this->actingAs($s['conventionUser'])
            ->post(route('sections.store', [$s['convention'], $floor]), [
                'name' => fake()->word()." CU-Create-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: ConventionUser should be able to create section on floor {$floor->id}"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7b: FloorUser can create on assigned floor, denied on unassigned
// ──────────────────────────────────────────────────────────────────────────────

it('allows FloorUser to create sections on assigned floor, denies on unassigned floor', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();

        // Assigned floor (floor1) → should succeed
        $response = $this->actingAs($s['floorUser'])
            ->post(route('sections.store', [$s['convention'], $s['floor1']]), [
                'name' => fake()->word()." FU-Assigned-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: FloorUser should create on assigned floor"
        );

        // Unassigned floor (floor2) → should be forbidden
        $response = $this->actingAs($s['floorUser'])
            ->post(route('sections.store', [$s['convention'], $s['floor2']]), [
                'name' => fake()->word()." FU-Unassigned-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(403,
            "Iteration {$i}: FloorUser should be denied on unassigned floor"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7c: SectionUser cannot create sections
// ──────────────────────────────────────────────────────────────────────────────

it('denies SectionUser from creating sections on any floor', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $floor = fake()->randomElement([$s['floor1'], $s['floor2']]);

        $response = $this->actingAs($s['sectionUser'])
            ->post(route('sections.store', [$s['convention'], $floor]), [
                'name' => fake()->word()." SU-Create-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(403,
            "Iteration {$i}: SectionUser should be denied creating sections"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7d: Owner and ConventionUser can update any section
// ──────────────────────────────────────────────────────────────────────────────

it('allows Owner to update any section across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $section = fake()->randomElement($s['sections']->all());

        $response = $this->actingAs($s['owner'])
            ->put(route('sections.update', $section), [
                'name' => fake()->word()." Owner-Update-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: Owner should be able to update section {$section->id}"
        );
    }
})->group('property', 'section-crud', 'authorization');

it('allows ConventionUser to update any section across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $section = fake()->randomElement($s['sections']->all());

        $response = $this->actingAs($s['conventionUser'])
            ->put(route('sections.update', $section), [
                'name' => fake()->word()." CU-Update-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: ConventionUser should be able to update section {$section->id}"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7e: FloorUser can update sections on assigned floor, denied on unassigned
// ──────────────────────────────────────────────────────────────────────────────

it('allows FloorUser to update sections on assigned floor, denies on unassigned floor', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();

        // Sections on floor1 (assigned) → should succeed
        $assignedSection = fake()->randomElement(
            $s['sections']->filter(fn ($sec) => $sec->floor_id === $s['floor1']->id)->all()
        );

        $response = $this->actingAs($s['floorUser'])
            ->put(route('sections.update', $assignedSection), [
                'name' => fake()->word()." FU-Assigned-Update-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: FloorUser should update section on assigned floor"
        );

        // Sections on floor2 (unassigned) → should be forbidden
        $unassignedSection = fake()->randomElement(
            $s['sections']->filter(fn ($sec) => $sec->floor_id === $s['floor2']->id)->all()
        );

        $response = $this->actingAs($s['floorUser'])
            ->put(route('sections.update', $unassignedSection), [
                'name' => fake()->word()." FU-Unassigned-Update-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(403,
            "Iteration {$i}: FloorUser should be denied updating section on unassigned floor"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7f: SectionUser can update assigned sections, denied on unassigned
// ──────────────────────────────────────────────────────────────────────────────

it('allows SectionUser to update assigned section, denies on unassigned sections', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();

        // Assigned section (sections[0]) → should succeed
        $response = $this->actingAs($s['sectionUser'])
            ->put(route('sections.update', $s['sections'][0]), [
                'name' => fake()->word()." SU-Assigned-Update-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: SectionUser should update assigned section"
        );

        // Unassigned section (sections[1], [2], or [3]) → should be forbidden
        $unassigned = fake()->randomElement([$s['sections'][1], $s['sections'][2], $s['sections'][3]]);

        $response = $this->actingAs($s['sectionUser'])
            ->put(route('sections.update', $unassigned), [
                'name' => fake()->word()." SU-Unassigned-Update-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(403,
            "Iteration {$i}: SectionUser should be denied updating unassigned section {$unassigned->id}"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7g: Owner and ConventionUser can delete any section
// ──────────────────────────────────────────────────────────────────────────────

it('allows Owner to delete any section across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $section = fake()->randomElement($s['sections']->all());

        $response = $this->actingAs($s['owner'])
            ->delete(route('sections.destroy', $section));

        expect($response->status())->toBe(302,
            "Iteration {$i}: Owner should be able to delete section {$section->id}"
        );
        expect(Section::find($section->id))->toBeNull(
            "Iteration {$i}: Section should be removed from database"
        );
    }
})->group('property', 'section-crud', 'authorization');

it('allows ConventionUser to delete any section across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $section = fake()->randomElement($s['sections']->all());

        $response = $this->actingAs($s['conventionUser'])
            ->delete(route('sections.destroy', $section));

        expect($response->status())->toBe(302,
            "Iteration {$i}: ConventionUser should be able to delete section {$section->id}"
        );
        expect(Section::find($section->id))->toBeNull(
            "Iteration {$i}: Section should be removed from database"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7h: FloorUser can delete sections on assigned floor, denied on unassigned
// ──────────────────────────────────────────────────────────────────────────────

it('allows FloorUser to delete sections on assigned floor, denies on unassigned floor', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();

        // Section on assigned floor (floor1) → should succeed
        $assignedSection = $s['sections']->filter(fn ($sec) => $sec->floor_id === $s['floor1']->id)->first();

        $response = $this->actingAs($s['floorUser'])
            ->delete(route('sections.destroy', $assignedSection));

        expect($response->status())->toBe(302,
            "Iteration {$i}: FloorUser should delete section on assigned floor"
        );
        expect(Section::find($assignedSection->id))->toBeNull(
            "Iteration {$i}: Section should be removed from database"
        );

        // Section on unassigned floor (floor2) → should be forbidden
        $unassignedSection = $s['sections']->filter(fn ($sec) => $sec->floor_id === $s['floor2']->id)->first();

        $response = $this->actingAs($s['floorUser'])
            ->delete(route('sections.destroy', $unassignedSection));

        expect($response->status())->toBe(403,
            "Iteration {$i}: FloorUser should be denied deleting section on unassigned floor"
        );
        expect(Section::find($unassignedSection->id))->not->toBeNull(
            "Iteration {$i}: Section on unassigned floor should still exist"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7i: SectionUser cannot delete any section
// ──────────────────────────────────────────────────────────────────────────────

it('denies SectionUser from deleting any section', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $section = fake()->randomElement($s['sections']->all());
        $sectionId = $section->id;

        $response = $this->actingAs($s['sectionUser'])
            ->delete(route('sections.destroy', $section));

        expect($response->status())->toBe(403,
            "Iteration {$i}: SectionUser should be denied deleting section {$sectionId}"
        );
        expect(Section::find($sectionId))->not->toBeNull(
            "Iteration {$i}: Section should still exist after denied deletion"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// myReport prop
// ──────────────────────────────────────────────────────────────────────────────

it('passes myReport to the section show page when user has reported', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];
    $section = $structure['sections']->first();

    $period = AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => false,
    ]);

    AttendanceReport::create([
        'attendance_period_id' => $period->id,
        'section_id' => $section->id,
        'attendance' => 42,
        'reported_by' => $owner->id,
        'reported_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get(route('sections.show', $section));

    $response->assertInertia(fn ($page) => $page
        ->component('sections/show')
        ->has('myReport')
        ->where('myReport.attendance', 42)
    );
});

it('passes myReport as null when user has not reported', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];
    $section = $structure['sections']->first();

    AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => false,
    ]);

    $response = $this->actingAs($owner)->get(route('sections.show', $section));

    $response->assertInertia(fn ($page) => $page
        ->component('sections/show')
        ->where('myReport', null)
    );
});
