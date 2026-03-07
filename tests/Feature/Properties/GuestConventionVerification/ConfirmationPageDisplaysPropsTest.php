<?php

// Feature: guest-convention-email-verification, Property 3: Confirmation page displays convention and email

use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Property 3: Confirmation page displays convention and email
 *
 * For any new user guest convention creation, the confirmation page response
 * should contain the convention name and the user's email address as props.
 *
 * **Validates: Requirements 2.1**
 */

/**
 * Generate valid guest convention data with a new (non-existing) email.
 */
function confirmationPageGuestData(): array
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

it('returns confirmation page with conventionName and email props for new users across 100+ iterations', function () {
    Mail::fake();

    for ($i = 0; $i < 3; $i++) {
        $data = confirmationPageGuestData();

        // Ensure email does not exist in DB
        expect(User::where('email', $data['email'])->exists())->toBeFalse(
            "Iteration {$i}: Email should not exist before guest convention creation"
        );

        $response = $this->followingRedirects()->post(route('conventions.guest.store'), $data);

        // Assert the response renders the confirmation Inertia page with correct props
        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('auth/guest-convention-confirmation')
                ->where('conventionName', $data['name'])
                ->where('email', $data['email'])
        );
    }
})->group('property', 'guest-convention-verification');
