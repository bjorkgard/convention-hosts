<?php

// Feature: guest-convention-email-verification, Property 5: Set password page displays user email

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Property 5: Set password page displays user email
 *
 * For any user and convention with a valid signed URL, the set password page
 * should render with the user's email address available as a prop.
 *
 * **Validates: Requirements 4.1**
 */
it('renders set password page with user email prop for valid signed URLs across 100+ iterations', function () {
    for ($i = 0; $i < 100; $i++) {
        $user = User::factory()->create([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_confirmed' => false,
        ]);

        $convention = Convention::factory()->create([
            'name' => fake()->company().' Convention',
        ]);

        // Attach user to convention as owner
        $convention->users()->attach($user->id);

        $signedUrl = URL::temporarySignedRoute(
            'guest-verification.show',
            now()->addHours(24),
            ['user' => $user->id, 'convention' => $convention->id]
        );

        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('auth/guest-convention-set-password', false)
            ->where('user.email', $user->email)
            ->where('user.id', $user->id)
            ->where('user.first_name', $user->first_name)
            ->where('user.last_name', $user->last_name)
            ->where('convention.id', $convention->id)
            ->where('convention.name', $convention->name)
        );
    }
})->group('property', 'guest-convention-verification');
