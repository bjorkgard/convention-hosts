<?php

use App\Models\User;

test('accepted consent still trusts known optional cookies', function () {
    config()->set('consent.current_policy_version', 7);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_ACCEPTED,
        'consent_version' => 7,
        'consent_decided_at' => now()->subMinute(),
        'consent_updated_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)
        ->withUnencryptedCookies([
            'appearance' => 'dark',
            'theme' => 'apple',
            'sidebar_state' => 'false',
        ])
        ->get(route('conventions.index'));

    $response->assertOk()
        ->assertSee("const appearance = 'dark';", false)
        ->assertInertia(fn ($page) => $page->where('sidebarOpen', false));

    expect(rootHtmlTag($response))->toContain('class="dark"')
        ->and(rootHtmlTag($response))->toContain('data-theme="apple"')
        ->and(knownOptionalResponseCookieNames($response))->toBeEmpty();
});

test('declined consent ignores known optional cookies and forgets them on the response', function () {
    config()->set('consent.current_policy_version', 7);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 7,
        'consent_decided_at' => now()->subMinute(),
        'consent_updated_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)
        ->withUnencryptedCookies([
            'appearance' => 'dark',
            'theme' => 'apple',
            'sidebar_state' => 'false',
        ])
        ->get(route('conventions.index'));

    $response->assertOk()
        ->assertSee('data-theme="default"', false)
        ->assertSee("const appearance = 'system';", false);

    expect(rootHtmlTag($response))->not->toContain('class="dark"')
        ->and(rootHtmlTag($response))->toContain('data-theme="default"')
        ->and(knownOptionalResponseCookieNames($response))->toBe([
        'appearance',
        'theme',
        'sidebar_state',
    ]);
});

test('undecided consent behaves the same as declined for optional cookie trust', function () {
    config()->set('consent.current_policy_version', 7);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withUnencryptedCookies([
            'appearance' => 'dark',
            'theme' => 'apple',
            'sidebar_state' => 'false',
        ])
        ->get(route('conventions.index'));

    $response->assertOk()
        ->assertSee('data-theme="default"', false)
        ->assertSee("const appearance = 'system';", false);

    expect(rootHtmlTag($response))->not->toContain('class="dark"')
        ->and(rootHtmlTag($response))->toContain('data-theme="default"')
        ->and(knownOptionalResponseCookieNames($response))->toBe([
        'appearance',
        'theme',
        'sidebar_state',
    ]);
});

test('essential auth and session behavior continues while optional cookies are denied', function () {
    config()->set('consent.current_policy_version', 7);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 7,
        'consent_decided_at' => now()->subMinute(),
        'consent_updated_at' => now()->subMinute(),
    ]);

    $response = $this->withUnencryptedCookies([
        'appearance' => 'dark',
        'theme' => 'apple',
        'sidebar_state' => 'false',
    ])->followingRedirects()->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);

    $response->assertOk()
        ->assertSee('data-theme="default"', false);

    expect(knownOptionalResponseCookieNames($response))->toBe([
        'appearance',
        'theme',
        'sidebar_state',
    ])
        ->and(responseCookieNames($response))->toContain(config('session.cookie'))
        ->and(responseCookieNames($response))->toContain('XSRF-TOKEN');

    $this->get(route('conventions.index'))->assertOk();
    $this->assertAuthenticatedAs($user);
});

function responseCookieNames(\Illuminate\Testing\TestResponse $response): array
{
    return array_values(array_map(
        static fn ($cookie) => $cookie->getName(),
        $response->headers->getCookies()
    ));
}

function knownOptionalResponseCookieNames(\Illuminate\Testing\TestResponse $response): array
{
    return array_values(array_filter(
        responseCookieNames($response),
        static fn (string $cookieName) => in_array($cookieName, ['appearance', 'theme', 'sidebar_state'], true)
    ));
}

function rootHtmlTag(\Illuminate\Testing\TestResponse $response): string
{
    preg_match('/<html[^>]*>/', $response->getContent(), $matches);

    return $matches[0] ?? '';
}
