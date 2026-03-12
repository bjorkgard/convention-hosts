<?php

use App\Actions\Consent\RecordUserConsentAction;
use App\Models\User;
use App\Support\Consent\UserConsentResolver;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('persists an initial accepted consent decision with version and timestamps', function () {
    config()->set('consent.current_policy_version', 1);

    $timestamp = Carbon::parse('2026-03-12 09:00:00', config('app.timezone'));
    Carbon::setTestNow($timestamp);
    $expectedTimestamp = now()->toJSON();

    $user = User::factory()->create();

    $recordedUser = app(RecordUserConsentAction::class)->execute($user, User::CONSENT_STATE_ACCEPTED);

    expect($recordedUser->consent_state)->toBe(User::CONSENT_STATE_ACCEPTED)
        ->and($recordedUser->consent_version)->toBe(1)
        ->and($recordedUser->consent_decided_at?->toJSON())->toBe($expectedTimestamp)
        ->and($recordedUser->consent_updated_at?->toJSON())->toBe($expectedTimestamp);
});

it('overwrites an existing explicit decision and preserves decided_at while updating updated_at', function () {
    config()->set('consent.current_policy_version', 1);

    $initialTimestamp = Carbon::parse('2026-03-12 09:00:00', config('app.timezone'));
    $updatedTimestamp = Carbon::parse('2026-03-12 10:30:00', config('app.timezone'));

    Carbon::setTestNow($initialTimestamp);
    $expectedInitialTimestamp = now()->toJSON();

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_ACCEPTED,
        'consent_version' => 1,
        'consent_decided_at' => $initialTimestamp,
        'consent_updated_at' => $initialTimestamp,
    ]);

    Carbon::setTestNow($updatedTimestamp);
    $expectedUpdatedTimestamp = now()->toJSON();

    $recordedUser = app(RecordUserConsentAction::class)->execute($user, User::CONSENT_STATE_DECLINED);

    expect($recordedUser->consent_state)->toBe(User::CONSENT_STATE_DECLINED)
        ->and($recordedUser->consent_version)->toBe(1)
        ->and($recordedUser->consent_decided_at?->toJSON())->toBe($expectedInitialTimestamp)
        ->and($recordedUser->consent_updated_at?->toJSON())->toBe($expectedUpdatedTimestamp);
});

it('resolves a previously written decision back to undecided after a version bump', function () {
    config()->set('consent.current_policy_version', 1);

    $user = User::factory()->create();

    app(RecordUserConsentAction::class)->execute($user, User::CONSENT_STATE_ACCEPTED);

    config()->set('consent.current_policy_version', 2);

    $resolved = app(UserConsentResolver::class)->resolve($user->fresh());

    expect($resolved)->toBe([
        'state' => User::CONSENT_STATE_UNDECIDED,
        'version' => 2,
        'allowOptionalStorage' => false,
        'decidedAt' => null,
        'updatedAt' => null,
    ]);
});
