<?php

use App\Models\Section;
use App\Models\User;

// Feature: hearing-loop-section, Property 5: Daily reset preserves hearing_loop
// Validates: Requirements 8.1

it('preserves hearing_loop values after daily occupancy reset', function () {
    $occupancyOptions = [0, 10, 25, 50, 75, 100];

    for ($i = 0; $i < 100; $i++) {
        $user = User::factory()->create();
        $hearingLoop = fake()->boolean();
        $numberOfSeats = fake()->numberBetween(50, 500);
        $occupancy = fake()->randomElement($occupancyOptions);
        $availableSeats = (int) round($numberOfSeats * (1 - ($occupancy / 100)));

        $section = Section::factory()->create([
            'hearing_loop' => $hearingLoop,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => $occupancy,
            'available_seats' => $availableSeats,
            'last_occupancy_updated_by' => $user->id,
            'last_occupancy_updated_at' => now()->subMinutes(fake()->numberBetween(1, 1440)),
        ]);

        // Act: Run the daily reset command
        $this->artisan('app:reset-daily-occupancy')->assertSuccessful()->execute();

        // Assert: hearing_loop is unchanged
        $fresh = $section->fresh();

        expect($fresh->hearing_loop)->toBe($hearingLoop, "Iteration {$i}: hearing_loop should remain {$hearingLoop} after reset")
            ->and($fresh->hearing_loop)->toBeBool("Iteration {$i}: hearing_loop should still be a boolean after reset")
            // Verify the reset actually ran by checking occupancy fields
            ->and($fresh->occupancy)->toBe(0, "Iteration {$i}: occupancy should be reset to 0")
            ->and($fresh->available_seats)->toBe($fresh->number_of_seats, "Iteration {$i}: available_seats should equal number_of_seats after reset");

        // Cleanup for next iteration
        $section->delete();
        $user->delete();
    }
})->group('property', 'hearing-loop', 'occupancy-reset');
