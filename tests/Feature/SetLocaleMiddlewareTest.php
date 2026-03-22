<?php

use App\Models\Convention;
use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

it('applies authenticated user locale', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $owner = $structure['owner'];
    $convention = $structure['convention'];

    $owner->update(['locale' => 'en']);

    $this->actingAs($owner)
        ->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('applies convention locale from URL session when user locale is null', function () {
    $convention = Convention::factory()->create(['locale' => 'en']);

    // Simulate a URL session by visiting the section URL access route
    $this->get(route('url-access.section', $convention->section_url_token));

    $this->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('falls back to sv when no locale context exists', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $owner = $structure['owner'];
    $convention = $structure['convention'];

    // User has no locale set (null by default)
    $this->actingAs($owner)
        ->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->where('locale', 'sv'));
});

it('shares locale via Inertia props', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $owner = $structure['owner'];
    $convention = $structure['convention'];

    $owner->update(['locale' => 'en']);

    $this->actingAs($owner)
        ->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->has('locale'));
});

it('prioritizes user locale over convention locale', function () {
    $convention = Convention::factory()->create(['locale' => 'sv']);
    $user = User::factory()->create(['locale' => 'en']);
    ConventionTestHelper::attachUserToConvention($user, $convention, ['Owner', 'Administrator']);

    $this->actingAs($user)
        ->get(route('conventions.show', $convention))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});
