<?php

use App\Actions\UpdateOccupancyAction;
use App\Models\Section;
use App\Models\User;

/**
 * Property 27: Available Seats Occupancy Calculation
 *
 * For any section with number_of_seats N and available_seats A submitted,
 * the occupancy percentage should be calculated as: 100 - ((A / N) * 100),
 * rounded to the nearest integer, clamped between 0 and 100.
 *
 * Validates: Requirements 7.7
 */
it('calculates occupancy correctly from available seats', function () {
    // Run 100 iterations to test the property across different scenarios
    for ($i = 0; $i < 100; $i++) {
        // Arrange: Create a section with random capacity
        $section = Section::factory()->create([
            'number_of_seats' => fake()->numberBetween(50, 500),
            'occupancy' => 0,
            'available_seats' => 0,
        ]);
        
        $user = User::factory()->create();
        
        // Generate random available seats (0 to number_of_seats)
        $availableSeats = fake()->numberBetween(0, $section->number_of_seats);
        
        // Calculate expected occupancy
        $expectedOccupancy = 100 - (($availableSeats / $section->number_of_seats) * 100);
        $expectedOccupancy = max(0, min(100, round($expectedOccupancy)));
        
        // Act: Update occupancy using available seats
        $action = new UpdateOccupancyAction;
        $updatedSection = $action->execute($section, ['available_seats' => $availableSeats], $user);
        
        // Assert: Verify occupancy is calculated correctly
        expect($updatedSection->occupancy)->toBe((int) $expectedOccupancy)
            ->and($updatedSection->available_seats)->toBe($availableSeats)
            ->and($updatedSection->occupancy)->toBeGreaterThanOrEqual(0)
            ->and($updatedSection->occupancy)->toBeLessThanOrEqual(100);
        
        // Verify metadata is recorded
        expect($updatedSection->last_occupancy_updated_by)->toBe($user->id)
            ->and($updatedSection->last_occupancy_updated_at)->not->toBeNull();
        
        // Test edge cases within the loop
        if ($i % 10 === 0) {
            // Test with 0 available seats (100% occupancy)
            $result = $action->execute($section->fresh(), ['available_seats' => 0], $user);
            expect($result->occupancy)->toBe(100)
                ->and($result->available_seats)->toBe(0);
            
            // Test with all seats available (0% occupancy)
            $result = $action->execute($section->fresh(), ['available_seats' => $section->number_of_seats], $user);
            expect($result->occupancy)->toBe(0)
                ->and($result->available_seats)->toBe($section->number_of_seats);
            
            // Test with half available (50% occupancy)
            $halfSeats = (int) floor($section->number_of_seats / 2);
            $result = $action->execute($section->fresh(), ['available_seats' => $halfSeats], $user);
            expect($result->occupancy)->toBeGreaterThanOrEqual(45)
                ->and($result->occupancy)->toBeLessThanOrEqual(55); // Allow for rounding
        }
        
        // Cleanup for next iteration
        $section->delete();
        $user->delete();
    }
})->group('property', 'occupancy');

/**
 * Property 27 (Inverse): Occupancy to Available Seats Calculation
 *
 * For any section with occupancy percentage O provided, the available_seats
 * should be calculated as: number_of_seats * (1 - (O / 100)), rounded and non-negative.
 *
 * Validates: Requirements 7.3
 */
it('calculates available seats correctly from occupancy percentage', function () {
    // Run 100 iterations to test the property across different scenarios
    for ($i = 0; $i < 100; $i++) {
        // Arrange: Create a section with random capacity
        $section = Section::factory()->create([
            'number_of_seats' => fake()->numberBetween(50, 500),
            'occupancy' => 0,
            'available_seats' => 0,
        ]);
        
        $user = User::factory()->create();
        
        // Generate random occupancy percentage (0, 10, 25, 50, 75, 100)
        $occupancyOptions = [0, 10, 25, 50, 75, 100];
        $occupancy = fake()->randomElement($occupancyOptions);
        
        // Calculate expected available seats
        $expectedAvailableSeats = $section->number_of_seats * (1 - ($occupancy / 100));
        $expectedAvailableSeats = max(0, round($expectedAvailableSeats));
        
        // Act: Update occupancy using percentage
        $action = new UpdateOccupancyAction;
        $updatedSection = $action->execute($section, ['occupancy' => $occupancy], $user);
        
        // Assert: Verify available seats is calculated correctly
        expect($updatedSection->occupancy)->toBe($occupancy)
            ->and($updatedSection->available_seats)->toBe((int) $expectedAvailableSeats)
            ->and($updatedSection->available_seats)->toBeGreaterThanOrEqual(0)
            ->and($updatedSection->available_seats)->toBeLessThanOrEqual($section->number_of_seats);
        
        // Verify metadata is recorded
        expect($updatedSection->last_occupancy_updated_by)->toBe($user->id)
            ->and($updatedSection->last_occupancy_updated_at)->not->toBeNull();
        
        // Test edge cases within the loop
        if ($i % 10 === 0) {
            // Test with 0% occupancy (all seats available)
            $result = $action->execute($section->fresh(), ['occupancy' => 0], $user);
            expect($result->occupancy)->toBe(0)
                ->and($result->available_seats)->toBe($section->number_of_seats);
            
            // Test with 100% occupancy (no seats available)
            $result = $action->execute($section->fresh(), ['occupancy' => 100], $user);
            expect($result->occupancy)->toBe(100)
                ->and($result->available_seats)->toBe(0);
        }
        
        // Cleanup for next iteration
        $section->delete();
        $user->delete();
    }
})->group('property', 'occupancy');

