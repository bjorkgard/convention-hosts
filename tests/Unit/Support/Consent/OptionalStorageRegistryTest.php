<?php

use App\Models\User;
use App\Support\Consent\OptionalStorageRegistry;
use Symfony\Component\HttpFoundation\Response;

it('allows optional storage only for accepted consent on the current policy version', function () {
    config()->set('consent.current_policy_version', 5);

    $acceptedUser = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_ACCEPTED,
        'consent_version' => 5,
    ]);

    $declinedUser = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 5,
    ]);

    $undecidedUser = User::factory()->create();

    $outdatedUser = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_ACCEPTED,
        'consent_version' => 4,
    ]);

    $registry = app(OptionalStorageRegistry::class);

    expect($registry->allowsOptionalStorage($acceptedUser))->toBeTrue()
        ->and($registry->allowsOptionalStorage($declinedUser))->toBeFalse()
        ->and($registry->allowsOptionalStorage($undecidedUser))->toBeFalse()
        ->and($registry->allowsOptionalStorage($outdatedUser))->toBeFalse();
});

it('forgets only the known optional cookies', function () {
    $response = new Response('ok');
    $registry = app(OptionalStorageRegistry::class);

    $registry->forgetOptionalCookies($response);

    $forgottenNames = array_map(
        static fn ($cookie) => $cookie->getName(),
        $response->headers->getCookies()
    );

    expect($forgottenNames)->toBe($registry->optionalCookieNames())
        ->and($forgottenNames)->not->toContain(config('session.cookie'))
        ->and($forgottenNames)->not->toContain('XSRF-TOKEN')
        ->and($forgottenNames)->not->toContain('remember_web');
});

it('keeps the agreed safe defaults centralized', function () {
    $registry = app(OptionalStorageRegistry::class);

    expect($registry->fallbackAppearance())->toBe('system')
        ->and($registry->fallbackTheme())->toBe('default')
        ->and($registry->fallbackSidebarOpen())->toBeTrue()
        ->and($registry->optionalLocalStorageKeys())->toBe([
            'appearance',
            'theme',
            'install-prompt-dismissed',
        ]);
});
