<?php

use App\Actions\UpdateOccupancyAction;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Property 24: Occupancy Update Metadata Recording
 *
 * For any occupancy update on a section, the system should record the updating
 * user in last_occupancy_updated_by and the current timestamp in
 * last_occupancy_updated_at.
 *
 * **Validates: Requirements 6.7, 7.8**
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

it('records updating user and timestamp when occupancy is updated via dropdown', function () {
    $validOccupancies = [0, 10, 25, 50, 75, 100];
    $action = new UpdateOccupancyAction;

    for ($i = 0; $i < 20; $i++) {
        $numberOfSeats = fake()->numberBetween(50, 500);
        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => 0,
            'available_seats' => $numberOfSeats,
            'last_occupancy_updated_by' => null,
            'last_occupancy_updated_at' => null,
        ]);

        $occupancy = fake()->randomElement($validOccupancies);
        $beforeUpdate = now()->subSecond();

        $updatedSection = $action->execute($section, ['occupancy' => $occupancy], $this->owner);

        // Property: last_occupancy_updated_by records the updating user
        expect($updatedSection->last_occupancy_updated_by)->toBe($this->owner->id,
            "Iteration {$i}: last_occupancy_updated_by should be the user who made the update"
        );

        // Property: last_occupancy_updated_at records a timestamp
        expect($updatedSection->last_occupancy_updated_at)->not->toBeNull(
            "Iteration {$i}: last_occupancy_updated_at should be set after update"
        );

        // Property: timestamp is recent (within a reasonable window)
        expect($updatedSection->last_occupancy_updated_at->gte($beforeUpdate))->toBeTrue(
            "Iteration {$i}: last_occupancy_updated_at should be at or after the update time"
        );

        // Verify persistence in database
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'last_occupancy_updated_by' => $this->owner->id,
        ]);

        $section->delete();
    }
})->group('property', 'occupancy');

it('records updating user and timestamp when occupancy is updated via available_seats', function () {
    $action = new UpdateOccupancyAction;

    for ($i = 0; $i < 20; $i++) {
        $numberOfSeats = fake()->numberBetween(50, 500);
        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => 0,
            'available_seats' => $numberOfSeats,
            'last_occupancy_updated_by' => null,
            'last_occupancy_updated_at' => null,
        ]);

        $availableSeats = fake()->numberBetween(0, $numberOfSeats);
        $beforeUpdate = now()->subSecond();

        $updatedSection = $action->execute($section, ['available_seats' => $availableSeats], $this->owner);

        // Property: last_occupancy_updated_by records the updating user
        expect($updatedSection->last_occupancy_updated_by)->toBe($this->owner->id,
            "Iteration {$i}: last_occupancy_updated_by should be the user who submitted available_seats"
        );

        // Property: last_occupancy_updated_at records a timestamp
        expect($updatedSection->last_occupancy_updated_at)->not->toBeNull(
            "Iteration {$i}: last_occupancy_updated_at should be set after available_seats update"
        );

        expect($updatedSection->last_occupancy_updated_at->gte($beforeUpdate))->toBeTrue(
            "Iteration {$i}: last_occupancy_updated_at should be at or after the update time"
        );

        $section->delete();
    }
})->group('property', 'occupancy');

it('updates metadata to reflect the most recent updating user', function () {
    $action = new UpdateOccupancyAction;
    $userA = $this->owner;
    $userB = User::factory()->create();

    // Attach userB to the convention
    $this->convention->users()->attach($userB->id);
    DB::table('convention_user_roles')->insert([
        ['convention_id' => $this->convention->id, 'user_id' => $userB->id, 'role' => 'ConventionUser', 'created_at' => now()],
    ]);

    for ($i = 0; $i < 10; $i++) {
        $numberOfSeats = fake()->numberBetween(50, 500);
        $section = Section::factory()->create([
            'floor_id' => $this->floor->id,
            'number_of_seats' => $numberOfSeats,
            'occupancy' => 0,
            'available_seats' => $numberOfSeats,
        ]);

        // First update by userA
        $action->execute($section, ['occupancy' => fake()->randomElement([0, 10, 25, 50, 75, 100])], $userA);
        $section->refresh();

        expect($section->last_occupancy_updated_by)->toBe($userA->id,
            "Iteration {$i}: After userA update, metadata should reflect userA"
        );
        $timestampAfterA = $section->last_occupancy_updated_at;

        // Second update by userB
        $action->execute($section, ['occupancy' => fake()->randomElement([0, 10, 25, 50, 75, 100])], $userB);
        $section->refresh();

        // Property: metadata changes to reflect the most recent user
        expect($section->last_occupancy_updated_by)->toBe($userB->id,
            "Iteration {$i}: After userB update, metadata should reflect userB"
        );

        expect($section->last_occupancy_updated_at->gte($timestampAfterA))->toBeTrue(
            "Iteration {$i}: Timestamp should be updated to reflect the latest update"
        );

        $section->delete();
    }
})->group('property', 'occupancy');
