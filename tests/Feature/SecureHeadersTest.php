<?php

use App\Models\User;

test('responses include X-Content-Type-Options header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/conventions');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('responses include X-Frame-Options header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/conventions');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

test('responses include X-XSS-Protection header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/conventions');

    $response->assertHeader('X-XSS-Protection', '1; mode=block');
});

test('responses include Referrer-Policy header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/conventions');

    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('responses include Strict-Transport-Security header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/conventions');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('responses include Content-Security-Policy header with required directives', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/conventions');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self'")
        ->toContain("style-src 'self' 'unsafe-inline'")
        ->toContain("img-src 'self' data:")
        ->toContain("font-src 'self'")
        ->toContain("connect-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("base-uri 'self'")
        ->toContain("form-action 'self'");
});

test('CSP includes Vite dev server in local environment', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/conventions');

    $csp = $response->headers->get('Content-Security-Policy');

    // In testing environment (treated as local), Vite dev URLs should be present
    expect($csp)
        ->toContain('http://localhost:5173')
        ->toContain('ws://localhost:5173');
});

test('unauthenticated responses also include security headers', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Content-Security-Policy');
});
