<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * Unit tests for the guest convention set password page.
 *
 * Validates: Requirements 4.2, 4.5
 */

/**
 * Helper to create a user and convention for set password tests.
 */
function setPasswordTestSetup(): array
{
    $user = User::factory()->create([
        'email_confirmed' => false,
        'password' => bcrypt('random-temp-password'),
    ]);

    $convention = Convention::factory()->create();
    $convention->users()->attach($user->id);

    return [$user, $convention];
}

test('set password page renders without authentication with valid signed URL', function () {
    [$user, $convention] = setPasswordTestSetup();

    // Ensure no user is logged in
    expect(Auth::check())->toBeFalse();

    $signedUrl = URL::temporarySignedRoute(
        'guest-verification.show',
        now()->addHours(24),
        ['user' => $user->id, 'convention' => $convention->id]
    );

    $response = $this->get($signedUrl);

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/guest-convention-set-password')
            ->has('user')
            ->has('convention')
            ->where('user.email', $user->email)
    );

    // User should NOT be authenticated after viewing the page
    expect(Auth::check())->toBeFalse();
})->group('guest-convention-verification');

test('validation rejects missing password', function () {
    [$user, $convention] = setPasswordTestSetup();

    $response = $this->post(
        route('guest-verification.store', ['user' => $user->id, 'convention' => $convention->id]),
        [
            // No password provided
        ]
    );

    $response->assertSessionHasErrors('password');
})->group('guest-convention-verification');

test('validation rejects password without confirmation', function () {
    [$user, $convention] = setPasswordTestSetup();

    $response = $this->post(
        route('guest-verification.store', ['user' => $user->id, 'convention' => $convention->id]),
        [
            'password' => 'ValidP@ss1',
            // No password_confirmation provided
        ]
    );

    $response->assertSessionHasErrors('password');
})->group('guest-convention-verification');
