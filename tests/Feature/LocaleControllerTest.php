<?php

it('returns available locale codes', function () {
    $response = $this->getJson('/api/locales');

    $response->assertOk()
        ->assertJsonFragment(['en'])
        ->assertJsonFragment(['sv']);
});

it('returns merged translations for a valid locale', function () {
    $response = $this->getJson('/api/translations/en');

    $response->assertOk();

    $data = $response->json();

    // Should contain domain keys matching the translation files
    expect($data)->toHaveKey('auth');
    expect($data)->toHaveKey('common');
    expect($data)->toHaveKey('convention');
});

it('returns 404 for a nonexistent locale', function () {
    $response = $this->getJson('/api/translations/nonexistent');

    $response->assertNotFound();
});

it('allows unauthenticated access to locales endpoint', function () {
    // Ensure no user is authenticated
    $response = $this->getJson('/api/locales');

    $response->assertOk();
});

it('allows unauthenticated access to translations endpoint', function () {
    $response = $this->getJson('/api/translations/sv');

    $response->assertOk();
});
