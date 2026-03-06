<?php

use App\Models\Section;
use App\Models\User;

/**
 * Property 28: Daily Occupancy Reset
 *
 * For any section, when the daily reset task runs, occupancy should be
 * reset to 0, available_seats should be reset to number_of_seats (all
 * seats available), and last_occupancy_updated_by and
 * last_occupancy_updated_at should be cleared.
 *
 * Validates: Requirements 8.2, 8.3
 */
it('resets all sections occupancy and metadata when daily reset runs', function () {
    $occupancyOptions = [0, 10, 25, 50, 75, 100];

    for ($i = 0; $i < 100; $i++) {
        // Arrange: Create a random number of sections (1-10) with non-zero occupancy data
        $sectionCount = fake()->numberBetween(1, 10);
        $user = User::factory()->create();
        $sections = [];

        for ($j = 0; $j < $sectionCount; $j++) {
            $numberOfSeats = fake()->numberBetween(50, 500);
            $occupancy = fake()->randomElement($occupancyOptions);
            $availableSeats = (int) round($numberOfSeats * (1 - ($occupancy / 100)));

            $sections[] = Section::factory()->create([
                'number_of_seats' => $numberOfSeats,
                'occupancy' => $occupancy,
                'available_seats' => $availableSeats,
                'last_occupancy_updated_by' => $user->id,
                'last_occupancy_updated_at' => now()->subMinutes(fake()->numberBetween(1, 1440)),
            ]);
        }

        // Also include a section that already has zero occupancy (should have available_seats = number_of_seats after reset)
        $zeroOccupancySeats = fake()->numberBetween(50, 500);
        $sections[] = Section::factory()->create([
            'number_of_seats' => $zeroOccupancySeats,
            'occupancy' => 0,
            'available_seats' => $zeroOccupancySeats,
            'last_occupancy_updated_by' => null,
            'last_occupancy_updated_at' => null,
        ]);

        // Act: Run the daily reset command
        $exitCode = $this->artisan('app:reset-daily-occupancy')->assertSuccessful()->execute();

        // Assert: ALL sections have been reset (available_seats = number_of_seats)
        foreach ($sections as $section) {
            $fresh = $section->fresh();

            expect($fresh->occupancy)->toBe(0)
                ->and($fresh->available_seats)->toBe($fresh->number_of_seats)
                ->and($fresh->last_occupancy_updated_by)->toBeNull()
                ->and($fresh->last_occupancy_updated_at)->toBeNull();
        }

        // Cleanup for next iteration
        foreach ($sections as $section) {
            $section->delete();
        }
        $user->delete();
    }
})->group('property', 'occupancy-reset');
