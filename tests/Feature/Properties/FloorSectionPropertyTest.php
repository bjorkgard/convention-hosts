<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

/**
 * Property 20: Floor-Convention Association
 *
 * Every floor belongs to exactly one convention. Creating random numbers
 * of conventions with random numbers of floors should result in each
 * floor's convention_id pointing to the correct convention, the
 * floor->convention() relationship returning the correct convention,
 * and convention->floors() returning only floors belonging to that convention.
 *
 * **Validates: Requirements 6.2**
 */
it('associates each floor with exactly one convention via convention_id', function () {
    for ($iteration = 0; $iteration < 10; $iteration++) {
        $conventionCount = fake()->numberBetween(1, 3);
        $conventions = [];
        $expectedFloorIds = [];

        for ($c = 0; $c < $conventionCount; $c++) {
            $floorCount = fake()->numberBetween(1, 4);
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => $floorCount,
                'sections_per_floor' => 0,
                'with_owner' => false,
            ]);

            $convention = $structure['convention'];
            $conventions[] = $convention;
            $expectedFloorIds[$convention->id] = $structure['floors']->pluck('id')->sort()->values()->all();
        }

        // Verify each floor's convention_id and relationship
        foreach ($conventions as $convention) {
            $floorIds = $expectedFloorIds[$convention->id];

            foreach ($floorIds as $floorId) {
                $floor = Floor::find($floorId);

                expect($floor->convention_id)->toBe($convention->id,
                    "Iteration {$iteration}: Floor {$floorId} should belong to convention {$convention->id}"
                );

                expect($floor->convention->id)->toBe($convention->id,
                    "Iteration {$iteration}: floor->convention() should return convention {$convention->id}"
                );
            }

            // Verify convention->floors() returns only its own floors
            $conventionFloorIds = $convention->floors()->pluck('id')->sort()->values()->all();
            expect($conventionFloorIds)->toBe($floorIds,
                "Iteration {$iteration}: convention->floors() should return only floors belonging to convention {$convention->id}"
            );
        }
    }
})->group('property', 'floor-section');

it('ensures floors from one convention do not appear in another conventions floors', function () {
    for ($iteration = 0; $iteration < 5; $iteration++) {
        $structureA = ConventionTestHelper::createConventionWithStructure([
            'floors' => fake()->numberBetween(2, 4),
            'sections_per_floor' => 0,
            'with_owner' => false,
        ]);

        $structureB = ConventionTestHelper::createConventionWithStructure([
            'floors' => fake()->numberBetween(2, 4),
            'sections_per_floor' => 0,
            'with_owner' => false,
        ]);

        $floorsA = $structureA['floors']->pluck('id')->all();
        $floorsB = $structureB['floors']->pluck('id')->all();

        // No overlap between the two sets
        $overlap = array_intersect($floorsA, $floorsB);
        expect($overlap)->toBeEmpty(
            "Iteration {$iteration}: Floors from convention A should not appear in convention B"
        );

        // Convention A's floors() should not contain any of B's floor IDs
        $conventionAFloorIds = $structureA['convention']->floors()->pluck('id')->all();
        foreach ($floorsB as $floorBId) {
            expect(in_array($floorBId, $conventionAFloorIds))->toBeFalse(
                "Iteration {$iteration}: Convention A should not contain floor {$floorBId} from convention B"
            );
        }

        // Convention B's floors() should not contain any of A's floor IDs
        $conventionBFloorIds = $structureB['convention']->floors()->pluck('id')->all();
        foreach ($floorsA as $floorAId) {
            expect(in_array($floorAId, $conventionBFloorIds))->toBeFalse(
                "Iteration {$iteration}: Convention B should not contain floor {$floorAId} from convention A"
            );
        }
    }
})->group('property', 'floor-section');

it('cascades floor deletion when convention is deleted', function () {
    for ($iteration = 0; $iteration < 5; $iteration++) {
        $floorCount = fake()->numberBetween(1, 5);
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => $floorCount,
            'sections_per_floor' => 0,
            'with_owner' => false,
        ]);

        $convention = $structure['convention'];
        $floorIds = $structure['floors']->pluck('id')->all();

        // Verify floors exist
        foreach ($floorIds as $floorId) {
            $this->assertDatabaseHas('floors', ['id' => $floorId]);
        }

        $convention->delete();

        // Verify all floors are deleted
        foreach ($floorIds as $floorId) {
            $this->assertDatabaseMissing('floors', ['id' => $floorId]);
        }
    }
})->group('property', 'floor-section');

/**
 * Property 22: Section Optional Fields
 *
 * Sections can be created with or without optional fields (elder_friendly,
 * handicap_friendly, information). All combinations of optional fields
 * should result in successful creation with correct stored values.
 * Boolean fields default to false, information defaults to null.
 *
 * **Validates: Requirements 6.4, 6.5**
 */
it('creates sections with all combinations of optional fields', function () {
    $convention = Convention::factory()->create();
    $floor = Floor::factory()->create(['convention_id' => $convention->id]);

    $optionalCombinations = [
        ['elder_friendly' => true, 'handicap_friendly' => true, 'information' => true],
        ['elder_friendly' => true, 'handicap_friendly' => true, 'information' => false],
        ['elder_friendly' => true, 'handicap_friendly' => false, 'information' => true],
        ['elder_friendly' => true, 'handicap_friendly' => false, 'information' => false],
        ['elder_friendly' => false, 'handicap_friendly' => true, 'information' => true],
        ['elder_friendly' => false, 'handicap_friendly' => true, 'information' => false],
        ['elder_friendly' => false, 'handicap_friendly' => false, 'information' => true],
        ['elder_friendly' => false, 'handicap_friendly' => false, 'information' => false],
    ];

    foreach ($optionalCombinations as $idx => $combo) {
        for ($iteration = 0; $iteration < 3; $iteration++) {
            $attrs = [
                'floor_id' => $floor->id,
                'name' => fake()->word()." Section {$idx}-{$iteration}",
                'number_of_seats' => fake()->numberBetween(50, 500),
                'occupancy' => 0,
                'available_seats' => 0,
            ];

            if ($combo['elder_friendly']) {
                $attrs['elder_friendly'] = true;
            }
            if ($combo['handicap_friendly']) {
                $attrs['handicap_friendly'] = true;
            }
            if ($combo['information']) {
                $attrs['information'] = fake()->sentence();
            }

            $section = Section::create($attrs);

            expect($section->exists)->toBeTrue(
                "Combo {$idx}, iteration {$iteration}: Section should be created successfully"
            );

            // Verify stored values
            $section->refresh();

            expect($section->elder_friendly)->toBe($combo['elder_friendly'],
                "Combo {$idx}, iteration {$iteration}: elder_friendly should be ".($combo['elder_friendly'] ? 'true' : 'false')
            );
            expect($section->handicap_friendly)->toBe($combo['handicap_friendly'],
                "Combo {$idx}, iteration {$iteration}: handicap_friendly should be ".($combo['handicap_friendly'] ? 'true' : 'false')
            );

            if ($combo['information']) {
                expect($section->information)->not->toBeNull(
                    "Combo {$idx}, iteration {$iteration}: information should not be null when provided"
                );
            } else {
                expect($section->information)->toBeNull(
                    "Combo {$idx}, iteration {$iteration}: information should be null when not provided"
                );
            }
        }
    }
})->group('property', 'floor-section');

it('defaults boolean optional fields to false and information to null', function () {
    $convention = Convention::factory()->create();
    $floor = Floor::factory()->create(['convention_id' => $convention->id]);

    for ($iteration = 0; $iteration < 10; $iteration++) {
        $section = Section::create([
            'floor_id' => $floor->id,
            'name' => fake()->word()." Section {$iteration}",
            'number_of_seats' => fake()->numberBetween(50, 500),
            'occupancy' => 0,
            'available_seats' => 0,
        ]);

        $section->refresh();

        expect($section->elder_friendly)->toBeFalse(
            "Iteration {$iteration}: elder_friendly should default to false"
        );
        expect($section->handicap_friendly)->toBeFalse(
            "Iteration {$iteration}: handicap_friendly should default to false"
        );
        expect($section->information)->toBeNull(
            "Iteration {$iteration}: information should default to null"
        );
    }
})->group('property', 'floor-section');

it('stores random optional field values correctly', function () {
    $convention = Convention::factory()->create();
    $floor = Floor::factory()->create(['convention_id' => $convention->id]);

    for ($iteration = 0; $iteration < 15; $iteration++) {
        $elderFriendly = fake()->boolean();
        $handicapFriendly = fake()->boolean();
        $information = fake()->optional(0.5)->paragraph();

        $section = Section::create([
            'floor_id' => $floor->id,
            'name' => fake()->word()." Section {$iteration}",
            'number_of_seats' => fake()->numberBetween(50, 500),
            'occupancy' => 0,
            'available_seats' => 0,
            'elder_friendly' => $elderFriendly,
            'handicap_friendly' => $handicapFriendly,
            'information' => $information,
        ]);

        $section->refresh();

        expect($section->elder_friendly)->toBe($elderFriendly,
            "Iteration {$iteration}: elder_friendly should match input value"
        );
        expect($section->handicap_friendly)->toBe($handicapFriendly,
            "Iteration {$iteration}: handicap_friendly should match input value"
        );
        expect($section->information)->toBe($information,
            "Iteration {$iteration}: information should match input value"
        );
    }
})->group('property', 'floor-section');
