<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

/**
 * Property 11: Invitation Resend Rate Limiting
 *
 * For any user, resend invitation requests should be limited to 3 attempts per 60 minutes.
 *
 * **Validates: Requirements 3.9**
 */
beforeEach(function () {
    Mail::fake();

    // Register the invitation.show route needed by the resendInvitation controller.
    // Task 7.9 creates this route; we define it here so the controller can generate
    // signed URLs without throwing RouteNotFoundException.
    Route::get('invitation/{user}/{convention}', fn () => 'ok')->name('invitation.show');
    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();

    $this->owner = User::factory()->create();
    $this->convention = Convention::factory()->create();

    // Attach owner to convention
    $this->convention->users()->attach($this->owner->id);
    DB::table('convention_user_roles')->insert([
        'convention_id' => $this->convention->id,
        'user_id' => $this->owner->id,
        'role' => 'Owner',
        'created_at' => now(),
    ]);

    // Create target user attached to the convention
    $this->targetUser = User::factory()->create();
    $this->convention->users()->attach($this->targetUser->id);
    DB::table('convention_user_roles')->insert([
        'convention_id' => $this->convention->id,
        'user_id' => $this->targetUser->id,
        'role' => 'SectionUser',
        'created_at' => now(),
    ]);
});

it('allows the first 3 resend requests within 60 minutes', function () {
    actingAs($this->owner);

    $url = route('users.resendInvitation', [
        'convention' => $this->convention->id,
        'user' => $this->targetUser->id,
    ]);

    // First 3 requests should succeed (302 redirect)
    for ($i = 1; $i <= 3; $i++) {
        $response = post($url);
        $response->assertStatus(302, "Request #{$i} should succeed with 302 redirect");
    }
});

it('rate limits the 4th resend request within 60 minutes', function () {
    actingAs($this->owner);

    $url = route('users.resendInvitation', [
        'convention' => $this->convention->id,
        'user' => $this->targetUser->id,
    ]);

    // Exhaust the 3 allowed requests
    for ($i = 1; $i <= 3; $i++) {
        post($url)->assertStatus(302);
    }

    // 4th request should be rate limited (429)
    $response = post($url);
    $response->assertStatus(429);
});

it('enforces rate limiting across multiple random iterations', function () {
    // Property-based: run multiple iterations with random target users
    for ($iteration = 0; $iteration < 10; $iteration++) {
        // Clear rate limiter state between iterations so each starts fresh
        // The throttle middleware stores state in the cache, so flush it
        Cache::flush();

        // Create a fresh target user for each iteration
        $targetUser = User::factory()->create();
        $this->convention->users()->attach($targetUser->id);
        DB::table('convention_user_roles')->insert([
            'convention_id' => $this->convention->id,
            'user_id' => $targetUser->id,
            'role' => fake()->randomElement(['ConventionUser', 'FloorUser', 'SectionUser']),
            'created_at' => now(),
        ]);

        actingAs($this->owner);

        $url = route('users.resendInvitation', [
            'convention' => $this->convention->id,
            'user' => $targetUser->id,
        ]);

        $successCount = 0;
        $rateLimitedCount = 0;

        // Make 5 requests — first 3 should succeed, last 2 should be rate limited
        for ($i = 1; $i <= 5; $i++) {
            $response = post($url);
            $status = $response->getStatusCode();

            if ($status === 302) {
                $successCount++;
            } elseif ($status === 429) {
                $rateLimitedCount++;
            }
        }

        // Property: exactly 3 requests succeed, remaining are rate limited
        expect($successCount)->toBe(3, "Iteration {$iteration}: Expected exactly 3 successful requests, got {$successCount}");
        expect($rateLimitedCount)->toBe(2, "Iteration {$iteration}: Expected 2 rate-limited requests, got {$rateLimitedCount}");
    }
});
