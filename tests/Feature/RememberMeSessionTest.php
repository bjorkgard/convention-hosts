<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\post;

/**
 * Property 5: Remember Me Session Duration
 *
 * For any login with "remember me" selected, the session cookie should be
 * valid for 30 days; without it, the session should expire on browser close.
 *
 * **Validates: Requirements 2.3**
 */
beforeEach(function () {
    Cache::flush();
});

it('sets remember_token in database when logging in with remember=true', function () {
    $user = User::factory()->create([
        'remember_token' => null,
    ]);

    expect($user->remember_token)->toBeNull();

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $user->refresh();

    expect($user->remember_token)->not->toBeNull()
        ->and($user->remember_token)->toBeString()
        ->and(strlen($user->remember_token))->toBeGreaterThan(0);
});

it('includes a remember cookie in the response when remember=true', function () {
    $user = User::factory()->create([
        'remember_token' => null,
    ]);

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $cookies = $response->headers->getCookies();
    $rememberCookie = collect($cookies)->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

    expect($rememberCookie)->not->toBeNull('Expected a remember_web_* cookie in the response');

    // Verify the cookie expiry is set in the future (at least 30 days from now)
    $minimumExpiry = time() + (30 * 24 * 60 * 60);
    $actualExpiry = $rememberCookie->getExpiresTime();

    expect($actualExpiry)->toBeGreaterThanOrEqual($minimumExpiry, 'Remember cookie should be valid for at least 30 days');
});

it('does not set remember_token when logging in without remember', function () {
    $user = User::factory()->create([
        'remember_token' => null,
    ]);

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $user->refresh();

    expect($user->remember_token)->toBeNull();
});

it('does not set remember_token when remember is false', function () {
    $user = User::factory()->create([
        'remember_token' => null,
    ]);

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => false,
    ]);

    $user->refresh();

    expect($user->remember_token)->toBeNull();
});

it('does not include a remember cookie when logging in without remember', function () {
    $user = User::factory()->create([
        'remember_token' => null,
    ]);

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $cookies = $response->headers->getCookies();
    $rememberCookie = collect($cookies)->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

    expect($rememberCookie)->toBeNull('Expected no remember_web_* cookie when remember is not selected');
});

it('consistently sets remember_token across multiple random users with remember=true', function () {
    for ($i = 0; $i < 10; $i++) {
        $user = User::factory()->create([
            'remember_token' => null,
        ]);

        post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $user->refresh();

        expect($user->remember_token)->not->toBeNull("Iteration {$i}: remember_token should be set when remember=true")
            ->and($user->remember_token)->toBeString();

        // Logout for next iteration
        auth()->logout();
    }
});

it('consistently does not set remember_token across multiple random users without remember', function () {
    for ($i = 0; $i < 10; $i++) {
        $user = User::factory()->create([
            'remember_token' => null,
        ]);

        post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => (bool) fake()->randomElement([false, null, 0]),
        ]);

        $user->refresh();

        expect($user->remember_token)->toBeNull("Iteration {$i}: remember_token should not be set when remember is falsy");

        // Logout for next iteration
        auth()->logout();
    }
});
