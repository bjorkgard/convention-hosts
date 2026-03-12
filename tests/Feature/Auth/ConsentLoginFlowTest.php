<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\Helpers\ConventionTestHelper;

test('password login first delivers the shared undecided consent contract on the conventions show response', function () {
    config()->set('consent.current_policy_version', 5);

    $user = User::factory()->create();
    $structure = ConventionTestHelper::createConventionWithStructure([
        'owner' => $user,
    ]);

    $loginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    $target = route('conventions.show', $structure['convention'], absolute: false);

    $loginResponse->assertRedirect($target);

    $this->get($target)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/show')
            ->where('consent.state', User::CONSENT_STATE_UNDECIDED)
            ->where('consent.version', 5)
            ->where('consent.allowOptionalStorage', false)
            ->where('consent.decidedAt', null)
            ->where('consent.updatedAt', null));
});

test('password login first delivers the shared undecided consent contract on the conventions index response', function () {
    config()->set('consent.current_policy_version', 5);

    $user = User::factory()->create();

    ConventionTestHelper::attachUserToConvention(
        $user,
        ConventionTestHelper::createConventionWithStructure(['with_owner' => false])['convention'],
        ['ConventionUser'],
    );
    ConventionTestHelper::attachUserToConvention(
        $user,
        ConventionTestHelper::createConventionWithStructure(['with_owner' => false])['convention'],
        ['ConventionUser'],
    );

    $loginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    $target = route('conventions.index', absolute: false);

    $loginResponse->assertRedirect($target);

    $this->get($target)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_UNDECIDED)
            ->where('consent.version', 5)
            ->where('consent.allowOptionalStorage', false)
            ->where('consent.decidedAt', null)
            ->where('consent.updatedAt', null));
});

test('two factor completion first delivers the shared undecided consent contract on the conventions show response', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    config()->set('consent.current_policy_version', 6);

    $user = User::factory()->withTwoFactor()->create();
    $structure = ConventionTestHelper::createConventionWithStructure([
        'owner' => $user,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $target = route('conventions.show', $structure['convention'], absolute: false);

    $twoFactorResponse = $this->post(route('two-factor.login.store'), [
        'recovery_code' => 'recovery-code-1',
    ]);

    $this->assertAuthenticatedAs($user);

    $twoFactorResponse->assertRedirect($target);

    $this->get($target)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/show')
            ->where('consent.state', User::CONSENT_STATE_UNDECIDED)
            ->where('consent.version', 6)
            ->where('consent.allowOptionalStorage', false)
            ->where('consent.decidedAt', null)
            ->where('consent.updatedAt', null));
});

test('two factor completion first delivers the shared undecided consent contract on the conventions index response', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    config()->set('consent.current_policy_version', 6);

    $user = User::factory()->withTwoFactor()->create();

    ConventionTestHelper::attachUserToConvention(
        $user,
        ConventionTestHelper::createConventionWithStructure(['with_owner' => false])['convention'],
        ['ConventionUser'],
    );
    ConventionTestHelper::attachUserToConvention(
        $user,
        ConventionTestHelper::createConventionWithStructure(['with_owner' => false])['convention'],
        ['ConventionUser'],
    );

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $target = route('conventions.index', absolute: false);

    $twoFactorResponse = $this->post(route('two-factor.login.store'), [
        'recovery_code' => 'recovery-code-1',
    ]);

    $this->assertAuthenticatedAs($user);

    $twoFactorResponse->assertRedirect($target);

    $this->get($target)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('conventions/index')
            ->where('consent.state', User::CONSENT_STATE_UNDECIDED)
            ->where('consent.version', 6)
            ->where('consent.allowOptionalStorage', false)
            ->where('consent.decidedAt', null)
            ->where('consent.updatedAt', null));
});
