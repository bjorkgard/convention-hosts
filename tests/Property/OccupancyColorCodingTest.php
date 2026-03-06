<?php

/**
 * Property 29: Occupancy Color Coding
 *
 * For any occupancy percentage O in [0, 100]:
 * - 0 <= O <= 25  → green
 * - 26 <= O <= 50 → dark-green
 * - 51 <= O <= 75 → yellow
 * - 76 <= O <= 90 → orange
 * - 91 <= O <= 100 → red
 *
 * The color coding function must be total (defined for all valid inputs)
 * and must partition the range [0, 100] into exactly 5 non-overlapping bands.
 *
 * Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5
 */

/**
 * Helper that mirrors the frontend getOccupancyLevel logic.
 * This is the specification — the frontend hook must match this.
 */
function getOccupancyLevel(int $occupancy): string
{
    return match (true) {
        $occupancy <= 25 => 'green',
        $occupancy <= 50 => 'dark-green',
        $occupancy <= 75 => 'yellow',
        $occupancy <= 90 => 'orange',
        default => 'red',
    };
}

it('maps 0-25% occupancy to green', function () {
    for ($i = 0; $i < 3; $i++) {
        $occupancy = fake()->numberBetween(0, 25);
        expect(getOccupancyLevel($occupancy))->toBe('green');
    }
})->group('property', 'occupancy-color');

it('maps 26-50% occupancy to dark-green', function () {
    for ($i = 0; $i < 3; $i++) {
        $occupancy = fake()->numberBetween(26, 50);
        expect(getOccupancyLevel($occupancy))->toBe('dark-green');
    }
})->group('property', 'occupancy-color');

it('maps 51-75% occupancy to yellow', function () {
    for ($i = 0; $i < 3; $i++) {
        $occupancy = fake()->numberBetween(51, 75);
        expect(getOccupancyLevel($occupancy))->toBe('yellow');
    }
})->group('property', 'occupancy-color');

it('maps 76-90% occupancy to orange', function () {
    for ($i = 0; $i < 3; $i++) {
        $occupancy = fake()->numberBetween(76, 90);
        expect(getOccupancyLevel($occupancy))->toBe('orange');
    }
})->group('property', 'occupancy-color');

it('maps 91-100% occupancy to red', function () {
    for ($i = 0; $i < 3; $i++) {
        $occupancy = fake()->numberBetween(91, 100);
        expect(getOccupancyLevel($occupancy))->toBe('red');
    }
})->group('property', 'occupancy-color');

it('covers all boundary values correctly', function () {
    // Exact boundary values between each band
    expect(getOccupancyLevel(0))->toBe('green')
        ->and(getOccupancyLevel(25))->toBe('green')
        ->and(getOccupancyLevel(26))->toBe('dark-green')
        ->and(getOccupancyLevel(50))->toBe('dark-green')
        ->and(getOccupancyLevel(51))->toBe('yellow')
        ->and(getOccupancyLevel(75))->toBe('yellow')
        ->and(getOccupancyLevel(76))->toBe('orange')
        ->and(getOccupancyLevel(90))->toBe('orange')
        ->and(getOccupancyLevel(91))->toBe('red')
        ->and(getOccupancyLevel(100))->toBe('red');
})->group('property', 'occupancy-color');

it('partitions the full range with no gaps or overlaps', function () {
    // Exhaustively verify every value in [0, 100] maps to exactly one color
    $validColors = ['green', 'dark-green', 'yellow', 'orange', 'red'];

    for ($occupancy = 0; $occupancy <= 100; $occupancy++) {
        $color = getOccupancyLevel($occupancy);
        expect($validColors)->toContain($color);
    }
})->group('property', 'occupancy-color');
