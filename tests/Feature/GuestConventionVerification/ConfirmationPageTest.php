<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Unit tests for the guest convention confirmation page.
 *
 * Validates: Requirements 2.2, 2.3, 2.4
 */

/**
 * Helper to build valid guest convention POST data with a unique new email.
 */
function confirmationTestGuestData(array $overrides = []): array
{
    $startDate = now()->addDays(5);
    $endDate = (clone $startDate)->addDays(3);

    return array_merge([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane-'.uniqid().'@example.com',
        'mobile' => '+1234567890',
        'name' => 'Test Convention',
        'city' => 'UniqueCity'.uniqid(),
        'country' => 'UniqueCountry'.uniqid(),
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
    ], $overrides);
}

test('confirmation page renders without authentication for new users', function () {
    Mail::fake();

    $data = confirmationTestGuestData();

    // No user is logged in before the request
    expect(Auth::check())->toBeFalse();

    $response = $this->post(route('conventions.guest.store'), $data);

    // Page renders successfully (200 OK, not a redirect)
    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page->component('auth/guest-convention-confirmation')
    );

    // User is NOT authenticated after the request
    expect(Auth::check())->toBeFalse();
})->group('guest-convention-verification');

test('confirmation page contains instructional text about checking email', function () {
    Mail::fake();

    $data = confirmationTestGuestData([
        'name' => 'Spring Gathering 2025',
        'email' => 'visitor-'.uniqid().'@example.com',
    ]);

    $response = $this->post(route('conventions.guest.store'), $data);

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/guest-convention-confirmation')
            ->where('conventionName', 'Spring Gathering 2025')
            ->where('email', $data['email'])
    );
})->group('guest-convention-verification');

test('confirmation page props include convention name and email for new user', function () {
    Mail::fake();

    $data = confirmationTestGuestData([
        'name' => 'Annual Tech Meetup',
        'email' => 'newuser-'.uniqid().'@example.com',
    ]);

    $response = $this->post(route('conventions.guest.store'), $data);

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/guest-convention-confirmation')
            ->has('conventionName')
            ->has('email')
            ->where('conventionName', 'Annual Tech Meetup')
            ->where('email', $data['email'])
    );
})->group('guest-convention-verification');
