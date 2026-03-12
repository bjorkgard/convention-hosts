<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

afterEach(function () {
    Carbon::setTestNow();
});

test('authenticated users can record an accepted consent decision', function () {
    config()->set('consent.current_policy_version', 8);

    $timestamp = Carbon::parse('2026-03-12 18:10:00', config('app.timezone'));
    Carbon::setTestNow($timestamp);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('conventions.index'))
        ->followingRedirects()
        ->post(route('consent.store'), [
            'state' => User::CONSENT_STATE_ACCEPTED,
        ]);

    $user->refresh();

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_ACCEPTED)
            ->where('consent.version', 8)
            ->where('consent.allowOptionalStorage', true)
            ->where('consent.decidedAt', $user->consent_decided_at?->toJSON())
            ->where('consent.updatedAt', $user->consent_updated_at?->toJSON()));

    expect($user->consent_state)->toBe(User::CONSENT_STATE_ACCEPTED)
        ->and($user->consent_version)->toBe(8)
        ->and($user->consent_decided_at?->toJSON())->toBe($timestamp->toJSON())
        ->and($user->consent_updated_at?->toJSON())->toBe($timestamp->toJSON());
});

test('authenticated users can record a declined consent decision', function () {
    config()->set('consent.current_policy_version', 8);

    $timestamp = Carbon::parse('2026-03-12 18:25:00', config('app.timezone'));
    Carbon::setTestNow($timestamp);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('conventions.index'))
        ->followingRedirects()
        ->post(route('consent.store'), [
            'state' => User::CONSENT_STATE_DECLINED,
        ]);

    $user->refresh();

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_DECLINED)
            ->where('consent.version', 8)
            ->where('consent.allowOptionalStorage', false)
            ->where('consent.decidedAt', $user->consent_decided_at?->toJSON())
            ->where('consent.updatedAt', $user->consent_updated_at?->toJSON()));

    expect($user->consent_state)->toBe(User::CONSENT_STATE_DECLINED)
        ->and($user->consent_version)->toBe(8)
        ->and($user->consent_decided_at?->toJSON())->toBe($timestamp->toJSON())
        ->and($user->consent_updated_at?->toJSON())->toBe($timestamp->toJSON());
});

test('invalid consent state is rejected with validation errors', function () {
    config()->set('consent.current_policy_version', 8);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('conventions.index'))
        ->post(route('consent.store'), [
            'state' => 'maybe-later',
        ]);

    $response->assertRedirect(route('conventions.index'))
        ->assertSessionHasErrors('state');

    expect($user->fresh()->consent_state)->toBeNull()
        ->and($user->fresh()->consent_version)->toBeNull()
        ->and($user->fresh()->consent_decided_at)->toBeNull()
        ->and($user->fresh()->consent_updated_at)->toBeNull();
});

test('guests cannot record consent decisions', function () {
    $response = $this->post(route('consent.store'), [
        'state' => User::CONSENT_STATE_ACCEPTED,
    ]);

    $response->assertRedirect(route('login'));
});
