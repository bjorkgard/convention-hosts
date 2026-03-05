<?php

use App\Actions\UpdateOccupancyAction;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/**
 * Property 25: Occupancy Dropdown Auto-Save
 *
 * For ANY valid occupancy value from the set {0, 10, 25, 50, 75, 100},
 * when occupancy is submitted via the dropdown, the system SHALL save
 * the value immediately and persist it in the database.
 *
 * **Validates: Requirements 7.3**
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

it('auto-saves each valid occupancy value when selected from dropdown', function () {
    $validOccupancies = [0, 10, 25, 50, 75, 100];
    $action = new UpdateOccupancyAction;

    foreach ($validOccupancies as $occupancy) {
        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => 200,
            'occupancy' => 0,
            'available_seats' => 200,
        ]);

        // Simulate the auto-save that happens when dropdown selection triggers PATCH
        $updatedSection = $action->execute($section, ['occupancy' => $occupancy], $this->owner);

        // Property: the occupancy value is saved immediately
        expect($updatedSection->occupancy)->toBe($occupancy,
            "Occupancy {$occupancy}% should be saved immediately after dropdown selection"
        );

        // Verify persistence in database
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'occupancy' => $occupancy,
        ]);

        // Verify metadata is recorded (Requirements 7.8)
        expect($updatedSection->last_occupancy_updated_by)->toBe($this->owner->id)
            ->and($updatedSection->last_occupancy_updated_at)->not->toBeNull();

        $section->delete();
    }
})->group('property', 'occupancy');

it('persists occupancy across random sections and valid dropdown values', function () {
    $validOccupancies = [0, 10, 25, 50, 75, 100];
    $action = new UpdateOccupancyAction;

    for ($iteration = 0; $iteration < 30; $iteration++) {
        $numberOfSeats = fake()->numberBetween(50, 500);
        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => 0,
            'available_seats' => $numberOfSeats,
        ]);

        $occupancy = fake()->randomElement($validOccupancies);

        // Simulate dropdown auto-save
        $updatedSection = $action->execute($section, ['occupancy' => $occupancy], $this->owner);

        // Property: for ANY valid occupancy value, the saved value matches exactly
        expect($updatedSection->occupancy)->toBe($occupancy,
            "Iteration {$iteration}: Occupancy {$occupancy}% should be persisted for section with {$numberOfSeats} seats"
        );

        // Property: available_seats is recalculated consistently
        $expectedAvailableSeats = (int) max(0, round($numberOfSeats * (1 - ($occupancy / 100))));
        expect($updatedSection->available_seats)->toBe($expectedAvailableSeats,
            "Iteration {$iteration}: available_seats should be recalculated for occupancy {$occupancy}%"
        );

        // Verify database persistence
        $section->refresh();
        expect($section->occupancy)->toBe($occupancy);

        $section->delete();
    }
})->group('property', 'occupancy');

it('validates that only dropdown values are accepted via request validation', function () {
    $validOccupancies = [0, 10, 25, 50, 75, 100];
    $invalidValues = [5, 15, 30, 45, 60, 80, 99, -1, 101];

    actingAs($this->owner);

    $section = Section::factory()->create([
        'floor_id' => $this->floor->id,
        'number_of_seats' => 100,
        'occupancy' => 0,
        'available_seats' => 100,
    ]);

    // Property: invalid values are rejected by validation
    foreach ($invalidValues as $invalid) {
        $response = $this->patch(route('sections.updateOccupancy', $section), [
            'occupancy' => $invalid,
        ]);

        $response->assertSessionHasErrors('occupancy');

        // Original value should remain unchanged
        $section->refresh();
        expect($section->occupancy)->toBe(0,
            "Invalid occupancy value {$invalid} should not be saved"
        );
    }
})->group('property', 'occupancy');
