<?php

// Feature: guest-convention-email-verification, Property 1: Existing user auto-login preserved

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Property 1: Existing user auto-login preserved
 *
 * For any existing user in the database, when their email is provided during
 * guest convention creation, the system should log them in and redirect to
 * the convention show page.
 *
 * **Validates: Requirements 1.1**
 */

/**
 * Generate valid guest convention data using an existing user's details.
 */
function guestConventionDataForExistingUser(User $user): array
{
    $startDate = now()->addDays(fake()->numberBetween(1, 60));
    $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

    return [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'mobile' => $user->mobile ?? fake()->phoneNumber(),
        'name' => fake()->company().' Convention',
        'city' => fake()->unique()->city(),
        'country' => fake()->unique()->country(),
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ];
}

it('logs in existing user and redirects to convention show page across 100+ iterations', function () {
    for ($i = 0; $i < 100; $i++) {
        // Create a fresh existing user for each iteration
        $user = User::factory()->create([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'email_confirmed' => true,
        ]);

        $data = guestConventionDataForExistingUser($user);

        $response = $this->post(route('conventions.guest.store'), $data);

        // Assert user is logged in
        expect(Auth::check())->toBeTrue(
            "Iteration {$i}: User should be authenticated after guest convention creation with existing email"
        );
        expect(Auth::id())->toBe($user->id,
            "Iteration {$i}: Authenticated user should be the existing user"
        );

        // Assert redirect to convention show page
        $convention = $user->conventions()->latest('id')->first();
        expect($convention)->not->toBeNull(
            "Iteration {$i}: Convention should have been created"
        );

        $response->assertRedirect(route('conventions.show', $convention));

        // Logout for next iteration
        Auth::logout();
    }
})->group('property', 'guest-convention-verification');
