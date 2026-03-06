<?php

use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

/*
|--------------------------------------------------------------------------
| Mobile Responsiveness Tests
|--------------------------------------------------------------------------
| Validates: Requirements 18.4
|
| Verifies that all pages are accessible, contain responsive meta tags,
| PWA manifest link, and service worker registration.
|--------------------------------------------------------------------------
*/

describe('All pages return 200 status', function () {
    it('returns 200 for public pages', function () {
        $this->get(route('home'))->assertOk();
    });

    it('returns 200 for auth pages', function () {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('password.request'))->assertOk();
    });

    it('returns 200 for conventions index', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('conventions.index'))
            ->assertOk();
    });

    it('returns 200 for conventions create', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('conventions.create'))
            ->assertOk();
    });

    it('returns 200 for convention show page', function () {
        $setup = ConventionTestHelper::createConventionWithStructure();

        $this->actingAs($setup['owner'])
            ->get(route('conventions.show', $setup['convention']))
            ->assertOk();
    });

    it('returns 200 for floors index', function () {
        $setup = ConventionTestHelper::createConventionWithStructure();

        $this->actingAs($setup['owner'])
            ->get(route('floors.index', $setup['convention']))
            ->assertOk();
    });

    it('returns 200 for sections index', function () {
        $setup = ConventionTestHelper::createConventionWithStructure();
        $floor = $setup['floors']->first();

        $this->actingAs($setup['owner'])
            ->get(route('sections.index', [
                'convention' => $setup['convention']->id,
                'floor' => $floor->id,
            ]))
            ->assertOk();
    });

    it('returns 200 for section show', function () {
        $setup = ConventionTestHelper::createConventionWithStructure();
        $section = $setup['sections']->first();

        $this->actingAs($setup['owner'])
            ->get(route('sections.show', $section))
            ->assertOk();
    });

    it('returns 200 for users index', function () {
        $setup = ConventionTestHelper::createConventionWithStructure();

        $this->actingAs($setup['owner'])
            ->get(route('users.index', $setup['convention']))
            ->assertOk();
    });

    it('returns 200 for search index', function () {
        $setup = ConventionTestHelper::createConventionWithStructure();

        $this->actingAs($setup['owner'])
            ->get(route('search.index', $setup['convention']))
            ->assertOk();
    });
});

describe('HTML contains responsive meta tags', function () {
    it('includes viewport meta tag on public pages', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('name="viewport"', false);
        $response->assertSee('width=device-width, initial-scale=1', false);
    });

    it('includes viewport meta tag on authenticated pages', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('conventions.index'));

        $response->assertOk();
        $response->assertSee('name="viewport"', false);
        $response->assertSee('width=device-width, initial-scale=1', false);
    });

    it('includes theme-color meta tag', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('name="theme-color"', false);
    });
});

describe('PWA manifest and service worker', function () {
    it('includes manifest link in HTML', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('manifest.json', false);
    });

    it('includes service worker registration script', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('serviceWorker', false);
        $response->assertSee('sw.js', false);
    });

    it('has a valid manifest.json file in public directory', function () {
        $manifestPath = public_path('manifest.json');
        expect(file_exists($manifestPath))->toBeTrue();

        $manifest = json_decode(file_get_contents($manifestPath), true);
        expect($manifest)->toBeArray()
            ->and($manifest['name'])->not->toBeEmpty()
            ->and($manifest['display'])->toBe('standalone')
            ->and($manifest['icons'])->toBeArray()->not->toBeEmpty();
    });

    it('includes apple-touch-icon link', function () {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('apple-touch-icon', false);
    });
});
