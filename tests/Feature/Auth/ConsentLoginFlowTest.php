<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('password login first delivers the shared consent contract on the authenticated inertia response', function () {
    config()->set('consent.current_policy_version', 5);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 5,
        'consent_decided_at' => now()->subMinutes(10),
        'consent_updated_at' => now()->subMinutes(5),
    ]);

    $response = $this->followingRedirects()->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_DECLINED)
            ->where('consent.version', 5)
            ->where('consent.allowOptionalStorage', false)
            ->where('consent.decidedAt', $user->consent_decided_at?->toJSON())
            ->where('consent.updatedAt', $user->consent_updated_at?->toJSON()));
});

test('two factor completion delivers the same shared consent contract on the first authenticated inertia response', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    config()->set('consent.current_policy_version', 6);

    $user = User::factory()->withTwoFactor()->create([
        'consent_state' => User::CONSENT_STATE_ACCEPTED,
        'consent_version' => 6,
        'consent_decided_at' => now()->subMinutes(20),
        'consent_updated_at' => now()->subMinutes(2),
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $response = $this->followingRedirects()->post(route('two-factor.login.store'), [
        'recovery_code' => 'recovery-code-1',
    ]);

    $this->assertAuthenticatedAs($user);

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_ACCEPTED)
            ->where('consent.version', 6)
            ->where('consent.allowOptionalStorage', true)
            ->where('consent.decidedAt', $user->consent_decided_at?->toJSON())
            ->where('consent.updatedAt', $user->consent_updated_at?->toJSON()));
});
