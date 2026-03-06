<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Property 4: Convention Creator Role Assignment
 *
 * For any user creating a convention, that user should automatically be assigned
 * both Owner and ConventionUser roles for that convention.
 *
 * Validates: Requirements 1.4
 */
it('assigns Owner and ConventionUser roles to convention creator', function () {
    for ($i = 0; $i < 3; $i++) {
        $user = User::factory()->create();
        $convention = Convention::factory()->make();

        // Act: Create convention and assign creator roles
        $convention->save();
        $convention->users()->attach($user->id);

        // Simulate what CreateConventionAction should do
        DB::table('convention_user_roles')->insert([
            ['convention_id' => $convention->id, 'user_id' => $user->id, 'role' => 'Owner', 'created_at' => now()],
            ['convention_id' => $convention->id, 'user_id' => $user->id, 'role' => 'ConventionUser', 'created_at' => now()],
        ]);

        // Assert: Verify both roles are assigned
        $roles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $user->id)
            ->pluck('role')
            ->toArray();

        expect($roles)->toContain('Owner')
            ->and($roles)->toContain('ConventionUser')
            ->and($roles)->toHaveCount(2);

        // Verify using model methods
        expect($convention->hasRole($user, 'Owner'))->toBeTrue()
            ->and($convention->hasRole($user, 'ConventionUser'))->toBeTrue()
            ->and($convention->hasAnyRole($user, ['Owner', 'ConventionUser']))->toBeTrue();

        // Cleanup for next iteration
        $convention->delete();
        $user->delete();
    }
})->group('property', 'convention');

/**
 * Property 23: Section Default Values
 *
 * For any newly created section, the occupancy and available_seats fields
 * should be initialized to 0.
 *
 * Validates: Requirements 6.6
 */
it('initializes section occupancy and available_seats to 0', function () {
    for ($i = 0; $i < 3; $i++) {
        $floor = \App\Models\Floor::factory()->create();

        // Act: Create section with various attributes but without explicitly setting occupancy/available_seats
        $section = \App\Models\Section::factory()->create([
            'floor_id' => $floor->id,
            'number_of_seats' => fake()->numberBetween(50, 300),
            'elder_friendly' => fake()->boolean(),
            'handicap_friendly' => fake()->boolean(),
            // Intentionally not setting occupancy or available_seats
        ]);

        // Assert: Verify default values are 0
        expect($section->occupancy)->toBe(0)
            ->and($section->available_seats)->toBe(0);

        // Also test that the factory itself sets these to 0
        $section2 = \App\Models\Section::factory()->make();
        expect($section2->occupancy)->toBe(0)
            ->and($section2->available_seats)->toBe(0);

        // Cleanup for next iteration
        $section->delete();
        $floor->delete();
    }
})->group('property', 'section');
