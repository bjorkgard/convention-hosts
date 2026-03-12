<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('html root attributes use safe defaults when optional storage is disallowed', function () {
    config()->set('consent.current_policy_version', 9);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 9,
        'consent_decided_at' => now()->subMinute(),
        'consent_updated_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)
        ->withUnencryptedCookies([
            'appearance' => 'dark',
            'theme' => 'apple',
        ])
        ->get(route('conventions.index'));

    $response->assertOk()
        ->assertSee("const appearance = 'system';", false)
        ->assertDontSee("localStorage.getItem('theme')", false);

    expect(safeDefaultRootHtmlTag($response))->not->toContain('class="dark"')
        ->and(safeDefaultRootHtmlTag($response))->toContain('data-theme="default"');
});

test('inertia shared props expose the default sidebar state when consent disallows optional storage', function () {
    config()->set('consent.current_policy_version', 9);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 9,
        'consent_decided_at' => now()->subMinute(),
        'consent_updated_at' => now()->subMinute(),
    ]);

    $this->actingAs($user)
        ->withCookie('sidebar_state', 'false')
        ->get(route('conventions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('sidebarOpen', true));
});

test('previously supplied optional cookies no longer influence rendered output after decline', function () {
    config()->set('consent.current_policy_version', 9);

    $user = User::factory()->create([
        'consent_state' => User::CONSENT_STATE_DECLINED,
        'consent_version' => 9,
        'consent_decided_at' => now()->subMinute(),
        'consent_updated_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)
        ->withUnencryptedCookies([
            'appearance' => 'dark',
            'theme' => 'android',
            'sidebar_state' => 'false',
        ])
        ->get(route('conventions.index'));

    $response->assertOk()
        ->assertSee('data-theme="default"', false)
        ->assertSee("const appearance = 'system';", false)
        ->assertInertia(fn (Assert $page) => $page->where('sidebarOpen', true));

    expect(safeDefaultRootHtmlTag($response))->not->toContain('class="dark"')
        ->and(safeDefaultRootHtmlTag($response))->toContain('data-theme="default"');
});

function safeDefaultRootHtmlTag(\Illuminate\Testing\TestResponse $response): string
{
    preg_match('/<html[^>]*>/', $response->getContent(), $matches);

    return $matches[0] ?? '';
}
