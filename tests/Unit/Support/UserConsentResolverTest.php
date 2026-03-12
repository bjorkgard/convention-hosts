<?php

use App\Models\User;
use App\Support\Consent\UserConsentResolver;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('resolves no stored consent as undecided', function () {
    config()->set('consent.current_policy_version', 1);

    $user = User::factory()->create();

    expect(app(UserConsentResolver::class)->resolve($user))->toBe([
        'state' => User::CONSENT_STATE_UNDECIDED,
        'version' => 1,
        'allowOptionalStorage' => false,
        'decidedAt' => null,
        'updatedAt' => null,
    ]);
});

it('resolves accepted consent for the current version', function () {
    config()->set('consent.current_policy_version', 1);

    $timestamp = Carbon::parse('2026-03-12 09:00:00 UTC');
    Carbon::setTestNow($timestamp);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_ACCEPTED,
        'consent_version' => 1,
        'consent_decided_at' => $timestamp,
        'consent_updated_at' => $timestamp,
    ]);

    expect(app(UserConsentResolver::class)->resolve($user))->toBe([
        'state' => User::CONSENT_STATE_ACCEPTED,
        'version' => 1,
        'allowOptionalStorage' => true,
        'decidedAt' => $timestamp->toJSON(),
        'updatedAt' => $timestamp->toJSON(),
    ]);
});

it('resolves declined consent for the current version', function () {
    config()->set('consent.current_policy_version', 1);

    $timestamp = Carbon::parse('2026-03-12 09:15:00 UTC');
    Carbon::setTestNow($timestamp);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 1,
        'consent_decided_at' => $timestamp,
        'consent_updated_at' => $timestamp,
    ]);

    expect(app(UserConsentResolver::class)->resolve($user))->toBe([
        'state' => User::CONSENT_STATE_DECLINED,
        'version' => 1,
        'allowOptionalStorage' => false,
        'decidedAt' => $timestamp->toJSON(),
        'updatedAt' => $timestamp->toJSON(),
    ]);
});

it('invalidates stored consent when the version no longer matches', function () {
    config()->set('consent.current_policy_version', 2);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_ACCEPTED,
        'consent_version' => 1,
        'consent_decided_at' => now()->subDay(),
        'consent_updated_at' => now()->subHour(),
    ]);

    expect(app(UserConsentResolver::class)->resolve($user))->toBe([
        'state' => User::CONSENT_STATE_UNDECIDED,
        'version' => 2,
        'allowOptionalStorage' => false,
        'decidedAt' => null,
        'updatedAt' => null,
    ]);
});

it('falls back to undecided for malformed stored state', function () {
    config()->set('consent.current_policy_version', 1);

    $user = User::factory()->create([
        'consent_state' => 'maybe-later',
        'consent_version' => 1,
        'consent_decided_at' => now()->subDay(),
        'consent_updated_at' => now()->subHour(),
    ]);

    expect(app(UserConsentResolver::class)->resolve($user))->toBe([
        'state' => User::CONSENT_STATE_UNDECIDED,
        'version' => 1,
        'allowOptionalStorage' => false,
        'decidedAt' => null,
        'updatedAt' => null,
    ]);
});
