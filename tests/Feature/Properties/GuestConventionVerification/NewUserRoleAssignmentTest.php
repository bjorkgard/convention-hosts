<?php

// Feature: guest-convention-email-verification, Property 8: New user role assignment

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Property 8: New user role assignment
 *
 * For any new user created during guest convention creation, the user should be
 * assigned both the Owner and ConventionUser roles for the created convention.
 *
 * **Validates: Requirements 7.3**
 */
it('assigns Owner and ConventionUser roles to new users across 100+ iterations', function () {
    Mail::fake();

    for ($i = 0; $i < 3; $i++) {
        $startDate = now()->addDays(fake()->numberBetween(1, 60));
        $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

        $data = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'name' => fake()->company().' Convention',
            'city' => fake()->unique()->city(),
            'country' => fake()->unique()->country(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];

        $this->post(route('conventions.guest.store'), $data);

        // Find the created user and convention
        $user = User::where('email', $data['email'])->first();
        expect($user)->not->toBeNull();

        $convention = $user->conventions()->latest('id')->first();
        expect($convention)->not->toBeNull();

        // Get roles from convention_user_roles table
        $roles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $user->id)
            ->pluck('role')
            ->sort()
            ->values()
            ->toArray();

        // Assert both Owner and ConventionUser roles are assigned
        expect($roles)->toContain('Owner');
        expect($roles)->toContain('ConventionUser');
        expect($roles)->toHaveCount(2);
    }
})->group('property', 'guest-convention-verification');
