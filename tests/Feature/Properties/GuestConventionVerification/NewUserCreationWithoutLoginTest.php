<?php

// Feature: guest-convention-email-verification, Property 2: New user creation without login

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Property 2: New user creation without login
 *
 * For any email address that does not exist in the database, when provided during
 * guest convention creation, the system should create a user record with
 * email_confirmed=false, create the convention, render the confirmation page,
 * and NOT authenticate the user.
 *
 * **Validates: Requirements 1.2, 7.2**
 */

/**
 * Generate valid guest convention data with a new (non-existing) email.
 */
function guestConventionDataForNewUser(): array
{
    $startDate = now()->addDays(fake()->numberBetween(1, 60));
    $endDate = (clone $startDate)->addDays(fake()->numberBetween(1, 14));

    return [
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
}

it('creates new user with email_confirmed=false, renders confirmation page, and does not authenticate across 100+ iterations', function () {
    Mail::fake();

    for ($i = 0; $i < 3; $i++) {
        $data = guestConventionDataForNewUser();

        // Ensure email does not exist in DB
        expect(User::where('email', $data['email'])->exists())->toBeFalse(
            "Iteration {$i}: Email should not exist before guest convention creation"
        );

        $response = $this->followingRedirects()->post(route('conventions.guest.store'), $data);

        // Assert user was created in DB with email_confirmed=false
        $user = User::where('email', $data['email'])->first();
        expect($user)->not->toBeNull(
            "Iteration {$i}: User should have been created in the database"
        );
        expect((bool) $user->email_confirmed)->toBeFalse(
            "Iteration {$i}: New user should have email_confirmed=false"
        );

        // Assert the response renders the confirmation Inertia page
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('auth/guest-convention-confirmation'));

        // Assert the user is NOT authenticated
        expect(Auth::check())->toBeFalse(
            "Iteration {$i}: User should NOT be authenticated after guest convention creation with new email"
        );
    }
})->group('property', 'guest-convention-verification');
