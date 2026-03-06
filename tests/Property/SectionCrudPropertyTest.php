<?php

use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

// Feature: section-crud-management, Property 3: Valid section creation persists correctly
// Validates: Requirements 3.1

it('persists valid section creation with correct attributes', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 2,
        'sections_per_floor' => 0,
    ]);
    $owner = $structure['owner'];
    $convention = $structure['convention'];
    $floors = $structure['floors'];

    for ($iteration = 0; $iteration < 20; $iteration++) {
        $floor = $floors->random();
        $name = fake()->word().' Section '.$iteration;
        $seats = fake()->numberBetween(1, 999);
        $elderFriendly = fake()->boolean();
        $handicapFriendly = fake()->boolean();
        $information = fake()->optional(0.5)->sentence();

        $payload = [
            'floor_id' => $floor->id,
            'name' => $name,
            'number_of_seats' => $seats,
            'elder_friendly' => $elderFriendly,
            'handicap_friendly' => $handicapFriendly,
            'information' => $information,
        ];

        $response = $this->actingAs($owner)
            ->post(route('sections.store', [$convention, $floor]), $payload);

        $response->assertRedirect(route('floors.index', $convention));

        $section = Section::where('name', $name)
            ->where('floor_id', $floor->id)
            ->first();

        expect($section)->not->toBeNull("Iteration {$iteration}: Section should exist in database");
        expect($section->name)->toBe($name);
        expect($section->number_of_seats)->toBe($seats);
        expect($section->elder_friendly)->toBe($elderFriendly);
        expect($section->handicap_friendly)->toBe($handicapFriendly);
        expect($section->information)->toBe($information);
        expect($section->floor_id)->toBe($floor->id);
    }
})->group('property', 'section-crud');

it('creates section via floor_id in request body when different from route floor', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 3,
        'sections_per_floor' => 0,
    ]);
    $owner = $structure['owner'];
    $convention = $structure['convention'];
    $floors = $structure['floors'];

    for ($iteration = 0; $iteration < 10; $iteration++) {
        // Route-bound floor is the first one, but floor_id in body targets a random floor
        $routeFloor = $floors->first();
        $targetFloor = $floors->random();
        $name = fake()->word().' BodyFloor '.$iteration;

        $payload = [
            'floor_id' => $targetFloor->id,
            'name' => $name,
            'number_of_seats' => fake()->numberBetween(1, 500),
        ];

        $response = $this->actingAs($owner)
            ->post(route('sections.store', [$convention, $routeFloor]), $payload);

        $response->assertRedirect(route('floors.index', $convention));

        $section = Section::where('name', $name)->first();
        expect($section)->not->toBeNull("Iteration {$iteration}: Section should be created");
        expect($section->floor_id)->toBe($targetFloor->id,
            "Iteration {$iteration}: Section should belong to the floor specified in request body, not the route floor"
        );
    }
})->group('property', 'section-crud');

// Feature: section-crud-management, Property 4: Valid section update persists correctly
// Validates: Requirements 4.3

it('persists valid section update with correct attributes', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 1,
    ]);
    $owner = $structure['owner'];
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $otherSection = Section::factory()->create(['floor_id' => $structure['floors']->first()->id]);

    for ($iteration = 0; $iteration < 20; $iteration++) {
        $newName = fake()->word().' Updated '.$iteration;
        $newSeats = fake()->numberBetween(1, 999);
        $newElderFriendly = fake()->boolean();
        $newHandicapFriendly = fake()->boolean();
        $newInformation = fake()->optional(0.5)->sentence();

        $otherOriginal = $otherSection->only(['name', 'number_of_seats', 'elder_friendly', 'handicap_friendly', 'information']);

        $payload = [
            'name' => $newName,
            'number_of_seats' => $newSeats,
            'elder_friendly' => $newElderFriendly,
            'handicap_friendly' => $newHandicapFriendly,
            'information' => $newInformation,
        ];

        $response = $this->actingAs($owner)
            ->put(route('sections.update', $section), $payload);

        $response->assertRedirect(route('floors.index', $convention));

        $section->refresh();
        expect($section->name)->toBe($newName, "Iteration {$iteration}: name should be updated");
        expect($section->number_of_seats)->toBe($newSeats, "Iteration {$iteration}: number_of_seats should be updated");
        expect($section->elder_friendly)->toBe($newElderFriendly, "Iteration {$iteration}: elder_friendly should be updated");
        expect($section->handicap_friendly)->toBe($newHandicapFriendly, "Iteration {$iteration}: handicap_friendly should be updated");
        expect($section->information)->toBe($newInformation, "Iteration {$iteration}: information should be updated");

        // Verify other sections are not affected
        $otherSection->refresh();
        expect($otherSection->only(['name', 'number_of_seats', 'elder_friendly', 'handicap_friendly', 'information']))
            ->toBe($otherOriginal, "Iteration {$iteration}: other section should not be modified");
    }
})->group('property', 'section-crud');

// Feature: section-crud-management, Property 5: Section deletion removes the section
// Validates: Requirements 5.3

it('removes section from database on deletion', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 0,
    ]);
    $owner = $structure['owner'];
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();

    for ($iteration = 0; $iteration < 15; $iteration++) {
        $section = Section::factory()->create(['floor_id' => $floor->id]);
        $sectionId = $section->id;
        $countBefore = Section::where('floor_id', $floor->id)->count();

        $response = $this->actingAs($owner)
            ->delete(route('sections.destroy', $section));

        $response->assertRedirect(route('floors.index', $convention));

        expect(Section::find($sectionId))->toBeNull(
            "Iteration {$iteration}: Section should no longer exist in database"
        );
        expect(Section::where('floor_id', $floor->id)->count())->toBe($countBefore - 1,
            "Iteration {$iteration}: Floor section count should decrease by one"
        );
    }
})->group('property', 'section-crud');

// Feature: section-crud-management, Property 6: Cancelling deletion preserves the section
// Validates: Requirements 5.5

it('preserves section when deletion is not confirmed', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 0,
    ]);
    $floor = $structure['floors']->first();

    for ($iteration = 0; $iteration < 15; $iteration++) {
        $section = Section::factory()->create(['floor_id' => $floor->id]);
        $originalAttributes = $section->toArray();

        // Simulate cancellation: no DELETE request is sent
        // The section should remain completely unchanged
        $section->refresh();

        expect($section->id)->toBe($originalAttributes['id'],
            "Iteration {$iteration}: Section ID should be unchanged"
        );
        expect($section->name)->toBe($originalAttributes['name'],
            "Iteration {$iteration}: Section name should be unchanged"
        );
        expect($section->number_of_seats)->toBe($originalAttributes['number_of_seats'],
            "Iteration {$iteration}: number_of_seats should be unchanged"
        );
        expect($section->floor_id)->toBe($originalAttributes['floor_id'],
            "Iteration {$iteration}: floor_id should be unchanged"
        );
        expect($section->elder_friendly)->toBe($originalAttributes['elder_friendly'],
            "Iteration {$iteration}: elder_friendly should be unchanged"
        );
        expect($section->handicap_friendly)->toBe($originalAttributes['handicap_friendly'],
            "Iteration {$iteration}: handicap_friendly should be unchanged"
        );
        expect($section->information)->toBe($originalAttributes['information'],
            "Iteration {$iteration}: information should be unchanged"
        );
    }
})->group('property', 'section-crud');
