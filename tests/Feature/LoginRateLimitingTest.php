<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\post;

/**
 * Property 6: Login Rate Limiting
 *
 * For any IP address, after 5 failed login attempts within 1 minute,
 * subsequent login attempts should be blocked with a 429 status code.
 *
 * **Validates: Requirements 2.4, 2.6**
 */
beforeEach(function () {
    // Flush cache/rate limiter state before each test
    Cache::flush();
});

it('allows the first 5 failed login attempts', function () {
    $user = User::factory()->create();

    for ($i = 1; $i <= 5; $i++) {
        $response = post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        expect($response->getStatusCode())->not->toBe(429, "Request #{$i} should not be rate limited");
    }
});

it('rate limits the 6th failed login attempt', function () {
    $user = User::factory()->create();

    // Exhaust the 5 allowed attempts
    for ($i = 1; $i <= 5; $i++) {
        post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    // 6th attempt should be rate limited
    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});

it('enforces rate limiting per email+IP across multiple random iterations', function () {
    for ($iteration = 0; $iteration < 10; $iteration++) {
        // Clear rate limiter state between iterations
        Cache::flush();

        $user = User::factory()->create();

        $successCount = 0;
        $rateLimitedCount = 0;

        // Make 7 requests — first 5 should pass, last 2 should be rate limited
        for ($i = 1; $i <= 7; $i++) {
            $response = post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password-' . fake()->word(),
            ]);

            $status = $response->getStatusCode();

            if ($status === 429) {
                $rateLimitedCount++;
            } else {
                $successCount++;
            }
        }

        expect($successCount)->toBe(5, "Iteration {$iteration}: Expected exactly 5 non-rate-limited requests, got {$successCount}");
        expect($rateLimitedCount)->toBe(2, "Iteration {$iteration}: Expected 2 rate-limited requests, got {$rateLimitedCount}");
    }
});

it('does not rate limit a different email after another email is rate limited', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Exhaust rate limit for user1
    for ($i = 1; $i <= 5; $i++) {
        post(route('login.store'), [
            'email' => $user1->email,
            'password' => 'wrong-password',
        ]);
    }

    // Verify user1 is rate limited
    $response = post(route('login.store'), [
        'email' => $user1->email,
        'password' => 'wrong-password',
    ]);
    $response->assertStatus(429);

    // user2 should still be allowed
    $response = post(route('login.store'), [
        'email' => $user2->email,
        'password' => 'wrong-password',
    ]);

    expect($response->getStatusCode())->not->toBe(429);
});
