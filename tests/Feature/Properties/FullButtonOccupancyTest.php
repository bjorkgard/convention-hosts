<?php

use App\Actions\UpdateOccupancyAction;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Property 26: Full Button Sets 100% Occupancy
 *
 * For any section, clicking the "FULL" button should immediately set
 * occupancy to 100% and save it.
 *
 * **Validates: Requirements 7.5**
 */
beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->convention = Convention::factory()->create();

    $this->convention->users()->attach($this->owner->id);
    DB::table('convention_user_roles')->insert([
        ['convention_id' => $this->convention->id, 'user_id' => $this->owner->id, 'role' => 'Owner', 'created_at' => now()],
        ['convention_id' => $this->convention->id, 'user_id' => $this->owner->id, 'role' => 'ConventionUser', 'created_at' => now()],
    ]);

    $this->floor = Floor::factory()->create([
        'convention_id' => $this->convention->id,
    ]);
});

it('sets occupancy to 100% regardless of initial occupancy value', function () {
    $initialOccupancies = [0, 10, 25, 50, 75, 100];
    $action = new UpdateOccupancyAction;

    foreach ($initialOccupancies as $initial) {
        $numberOfSeats = 200;
        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => $initial,
            'available_seats' => (int) round($numberOfSeats * (1 - ($initial / 100))),
        ]);

        // Simulate the FULL button: sets occupancy to 100
        $updatedSection = $action->execute($section, ['occupancy' => 100], $this->owner);

        // Property: occupancy is always 100% after FULL
        expect($updatedSection->occupancy)->toBe(100,
            "Occupancy should be 100% after FULL button, was initially {$initial}%"
        );

        // Property: available_seats becomes 0 when occupancy is 100%
        expect($updatedSection->available_seats)->toBe(0,
            "Available seats should be 0 after FULL button, was initially {$initial}%"
        );

        // Verify persistence in database
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'occupancy' => 100,
            'available_seats' => 0,
        ]);

        $section->delete();
    }
})->group('property', 'occupancy');

it('records update metadata when full button is used', function () {
    $action = new UpdateOccupancyAction;

    for ($i = 0; $i < 10; $i++) {
        $numberOfSeats = fake()->numberBetween(50, 500);
        $initialOccupancy = fake()->randomElement([0, 10, 25, 50, 75]);

        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => $initialOccupancy,
            'available_seats' => (int) round($numberOfSeats * (1 - ($initialOccupancy / 100))),
            'last_occupancy_updated_by' => null,
            'last_occupancy_updated_at' => null,
        ]);

        $updatedSection = $action->execute($section, ['occupancy' => 100], $this->owner);

        // Property: last_occupancy_updated_by is recorded
        expect($updatedSection->last_occupancy_updated_by)->toBe($this->owner->id,
            "Iteration {$i}: last_occupancy_updated_by should be the acting user"
        );

        // Property: last_occupancy_updated_at is recorded
        expect($updatedSection->last_occupancy_updated_at)->not->toBeNull(
            "Iteration {$i}: last_occupancy_updated_at should be set"
        );

        $section->delete();
    }
})->group('property', 'occupancy');

it('sets occupancy to 100% for sections with varying seat capacities', function () {
    $action = new UpdateOccupancyAction;

    for ($i = 0; $i < 20; $i++) {
        $numberOfSeats = fake()->numberBetween(10, 1000);
        $initialOccupancy = fake()->randomElement([0, 10, 25, 50, 75]);
        $initialAvailable = (int) round($numberOfSeats * (1 - ($initialOccupancy / 100)));

        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => $initialOccupancy,
            'available_seats' => $initialAvailable,
        ]);

        // Simulate FULL button action (same as controller: ['occupancy' => 100])
        $updatedSection = $action->execute($section, ['occupancy' => 100], $this->owner);

        // Property: occupancy is always 100% regardless of capacity
        expect($updatedSection->occupancy)->toBe(100,
            "Iteration {$i}: Section with {$numberOfSeats} seats at {$initialOccupancy}% should become 100%"
        );

        // Property: available_seats is always 0 when full
        expect($updatedSection->available_seats)->toBe(0,
            "Iteration {$i}: Section with {$numberOfSeats} seats should have 0 available after FULL"
        );

        // Verify database persistence
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'occupancy' => 100,
            'available_seats' => 0,
        ]);

        $section->delete();
    }
})->group('property', 'occupancy');
