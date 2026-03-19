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

    $administrator = ConventionTestHelper::createUserWithRole($convention, 'Administrator');

    return [
        'convention' => $convention,
        'floor1' => $floor1,
        'floor2' => $floor2,
        'sections' => $sections,
        'owner' => $owner,
        'administrator' => $administrator,
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// Property 7a: Owner and Administrator can create sections on any floor
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

it('allows Administrator to create sections on any floor across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $floor = fake()->randomElement([$s['floor1'], $s['floor2']]);

        $response = $this->actingAs($s['administrator'])
            ->post(route('sections.store', [$s['convention'], $floor]), [
                'name' => fake()->word()." Admin-Create-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: Administrator should be able to create section on floor {$floor->id}"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7d: Owner and Administrator can update any section
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

it('allows Administrator to update any section across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $section = fake()->randomElement($s['sections']->all());

        $response = $this->actingAs($s['administrator'])
            ->put(route('sections.update', $section), [
                'name' => fake()->word()." Admin-Update-{$i}",
                'number_of_seats' => fake()->numberBetween(1, 500),
            ]);

        expect($response->status())->toBe(302,
            "Iteration {$i}: Administrator should be able to update section {$section->id}"
        );
    }
})->group('property', 'section-crud', 'authorization');

// ──────────────────────────────────────────────────────────────────────────────
// Property 7g: Owner and Administrator can delete any section
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

it('allows Administrator to delete any section across random iterations', function () {
    for ($i = 0; $i < 3; $i++) {
        $s = buildAuthorizationScenario();
        $section = fake()->randomElement($s['sections']->all());

        $response = $this->actingAs($s['administrator'])
            ->delete(route('sections.destroy', $section));

        expect($response->status())->toBe(302,
            "Iteration {$i}: Administrator should be able to delete section {$section->id}"
        );
        expect(Section::find($section->id))->toBeNull(
            "Iteration {$i}: Section should be removed from database"
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

it('passes myReport as null when there is no active period', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $owner = $structure['owner'];
    $section = $structure['sections']->first();

    // No attendance period created — activePeriod will be null

    $response = $this->actingAs($owner)->get(route('sections.show', $section));

    $response->assertInertia(fn ($page) => $page
        ->component('sections/show')
        ->where('myReport', null)
    );
});
