<?php

use App\Models\Floor;
use App\Models\Section;

// Feature: hearing-loop-section, Property 1: Section hearing_loop round-trip
// Validates: Requirements 1.1, 2.1, 2.2

it('persists hearing_loop value and reads it back as a boolean', function () {
    $floor = Floor::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $hearingLoop = fake()->boolean();

        $section = Section::factory()->create([
            'floor_id' => $floor->id,
            'hearing_loop' => $hearingLoop,
        ]);

        $persisted = Section::find($section->id);

        expect($persisted->hearing_loop)->toBe($hearingLoop, "Iteration {$i}: hearing_loop should round-trip correctly")
            ->and($persisted->hearing_loop)->toBeBool("Iteration {$i}: hearing_loop should be cast to boolean");

        $section->delete();
    }
})->group('property', 'hearing-loop');

it('defaults hearing_loop to false when omitted', function () {
    $floor = Floor::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $section = Section::create([
            'floor_id' => $floor->id,
            'name' => 'Section '.$i,
            'number_of_seats' => fake()->numberBetween(10, 500),
            'occupancy' => 0,
            'available_seats' => 0,
        ]);

        $persisted = Section::find($section->id);

        expect($persisted->hearing_loop)->toBeFalse("Iteration {$i}: hearing_loop should default to false when omitted")
            ->and($persisted->hearing_loop)->toBeBool("Iteration {$i}: hearing_loop should be cast to boolean");

        $section->delete();
    }
})->group('property', 'hearing-loop');

it('casts truthy and falsy values to boolean for hearing_loop', function () {
    $floor = Floor::factory()->create();

    $truthyValues = [true, 1, '1'];
    $falsyValues = [false, 0, '0'];

    for ($i = 0; $i < 100; $i++) {
        $useTruthy = fake()->boolean();
        $value = $useTruthy
            ? fake()->randomElement($truthyValues)
            : fake()->randomElement($falsyValues);

        $section = Section::factory()->create([
            'floor_id' => $floor->id,
            'hearing_loop' => $value,
        ]);

        $persisted = Section::find($section->id);

        expect($persisted->hearing_loop)->toBe($useTruthy, "Iteration {$i}: hearing_loop should cast value '{$value}' to ".($useTruthy ? 'true' : 'false'))
            ->and($persisted->hearing_loop)->toBeBool("Iteration {$i}: hearing_loop should always be a boolean");

        $section->delete();
    }
})->group('property', 'hearing-loop');
