<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Helpers\ConventionTestHelper;

test('authenticated inertia responses share the resolved consent contract', function () {
    config()->set('consent.current_policy_version', 3);

    Carbon::setTestNow('2026-03-12 09:15:00');

    $timestamp = now();
    $structure = ConventionTestHelper::createConventionWithStructure([
        'owner' => User::factory()->create([
            'consent_state' => User::CONSENT_STATE_ACCEPTED,
            'consent_version' => 3,
            'consent_decided_at' => $timestamp,
            'consent_updated_at' => $timestamp,
        ]),
    ]);

    $this->actingAs($structure['owner'])
        ->get(route('conventions.show', $structure['convention']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/show')
            ->where('consent', [
                'state' => User::CONSENT_STATE_ACCEPTED,
                'version' => 3,
                'allowOptionalStorage' => true,
                'decidedAt' => $timestamp->toJSON(),
                'updatedAt' => $timestamp->toJSON(),
            ]));

    Carbon::setTestNow();
});

test('authenticated inertia responses collapse invalid consent state to the undecided fallback contract', function () {
    config()->set('consent.current_policy_version', 4);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 2,
        'consent_decided_at' => now()->subDay(),
        'consent_updated_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->get(route('conventions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent', [
                'state' => User::CONSENT_STATE_UNDECIDED,
                'version' => 4,
                'allowOptionalStorage' => false,
                'decidedAt' => null,
                'updatedAt' => null,
            ]));
});
