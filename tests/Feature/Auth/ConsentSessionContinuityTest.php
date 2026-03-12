<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test('declined consent still establishes the essential authenticated session through login', function () {
    config()->set('consent.current_policy_version', 10);

    $user = declinedConsentUser();

    $response = $this->followingRedirects()->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_DECLINED)
            ->where('consent.allowOptionalStorage', false));

    expect(continuityResponseCookieNames($response))->toContain(config('session.cookie'))
        ->and(continuityResponseCookieNames($response))->toContain('XSRF-TOKEN');

    $this->get(route('conventions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_DECLINED));

    $this->assertAuthenticatedAs($user);
});

test('declined consent does not break later authenticated post requests in the same session', function () {
    config()->set('consent.current_policy_version', 10);

    $user = declinedConsentUser();

    $loginResponse = $this->followingRedirects()->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    expect(continuityResponseCookieNames($loginResponse))->toContain(config('session.cookie'))
        ->and(continuityResponseCookieNames($loginResponse))->toContain('XSRF-TOKEN');

    $response = $this->from(route('conventions.index'))
        ->followingRedirects()
        ->post(route('consent.store'), [
            'state' => User::CONSENT_STATE_ACCEPTED,
        ]);

    $user->refresh();

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_ACCEPTED)
            ->where('consent.allowOptionalStorage', true));

    expect($user->consent_state)->toBe(User::CONSENT_STATE_ACCEPTED)
        ->and(continuityResponseCookieNames($response))->toContain(config('session.cookie'))
        ->and(continuityResponseCookieNames($response))->toContain('XSRF-TOKEN');

    $this->assertAuthenticatedAs($user);
});

function declinedConsentUser(): User
{
    return User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 10,
        'consent_decided_at' => now()->subMinutes(5),
        'consent_updated_at' => now()->subMinutes(5),
    ]);
}

function continuityResponseCookieNames(TestResponse $response): array
{
    return array_values(array_map(
        static fn ($cookie) => $cookie->getName(),
        $response->headers->getCookies(),
    ));
}
