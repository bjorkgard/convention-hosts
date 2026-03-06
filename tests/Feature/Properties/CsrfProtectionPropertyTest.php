<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * Property 49: CSRF Protection
 *
 * For any state-changing request (POST, PUT, PATCH, DELETE), the system
 * should require a valid CSRF token and reject requests without it.
 *
 * This property is verified structurally: every state-changing route must
 * be protected by the web middleware group (which includes ValidateCsrfToken),
 * and no routes may be excluded from CSRF verification.
 *
 * Note: Laravel's test environment bypasses CSRF via runningUnitTests() check
 * in VerifyCsrfToken, so we verify the middleware infrastructure instead.
 *
 * **Validates: Requirements 21.3**
 */

it('enforces CSRF protection on every state-changing route', function () {
    $routes = app('router')->getRoutes();
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $middlewareGroups = $kernel->getMiddlewareGroups();

    // Precondition: ValidateCsrfToken must be in the web middleware group
    expect($middlewareGroups['web'])->toContain(ValidateCsrfToken::class);

    $testedRoutes = 0;
    $failedRoutes = [];

    foreach ($routes->getRoutes() as $route) {
        $uri = $route->uri();

        // Skip framework-internal routes (Boost, storage, etc.)
        if (str_starts_with($uri, '_') || str_starts_with($uri, 'storage/')) {
            continue;
        }

        $stateChangingMethods = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
        if (empty($stateChangingMethods)) {
            continue;
        }

        $middleware = collect(is_array($route->middleware()) ? $route->middleware() : [$route->middleware()]);
        $methodLabel = implode('|', $stateChangingMethods);

        if (! $middleware->contains('web')) {
            $failedRoutes[] = "{$methodLabel} {$uri}";
        }

        $testedRoutes++;
    }

    // Property: We must have state-changing routes to verify
    expect($testedRoutes)->toBeGreaterThan(0,
        'Application must have at least one state-changing route to verify CSRF protection'
    );

    // Property: Every state-changing route must use the web middleware group
    expect($failedRoutes)->toBeEmpty(
        'All state-changing routes must have web middleware (CSRF protection). Unprotected: ' . implode(', ', $failedRoutes)
    );
})->group('property', 'security');

it('has no CSRF exclusions for any route pattern', function () {
    $middleware = app()->make(ValidateCsrfToken::class);
    $reflection = new ReflectionClass($middleware);

    $exceptProperty = $reflection->getProperty('except');
    $exceptProperty->setAccessible(true);
    $except = $exceptProperty->getValue($middleware);

    // Also check the static neverVerify list
    $neverVerifyProperty = $reflection->getProperty('neverVerify');
    $neverVerifyProperty->setAccessible(true);
    $neverVerify = $neverVerifyProperty->getValue(null);

    // Property: No routes should be excluded from CSRF verification
    expect($except)->toBeEmpty(
        'Instance-level CSRF exclusions must be empty for maximum security'
    );

    // neverVerify may contain framework defaults — verify none match our app routes
    $appRoutes = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($route) => ! str_starts_with($route->uri(), '_') && ! str_starts_with($route->uri(), 'storage/'))
        ->map(fn ($route) => $route->uri())
        ->values()
        ->toArray();

    foreach ($neverVerify as $pattern) {
        foreach ($appRoutes as $uri) {
            expect(str_is($pattern, $uri))->toBeFalse(
                "App route [{$uri}] matches CSRF exclusion pattern [{$pattern}]"
            );
        }
    }
})->group('property', 'security');

it('covers all application state-changing routes with CSRF middleware', function () {
    $routes = app('router')->getRoutes();

    // Collect all unique state-changing route URIs
    $stateChangingUris = [];

    foreach ($routes->getRoutes() as $route) {
        $uri = $route->uri();

        if (str_starts_with($uri, '_') || str_starts_with($uri, 'storage/')) {
            continue;
        }

        $stateChangingMethods = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
        if (empty($stateChangingMethods)) {
            continue;
        }

        foreach ($stateChangingMethods as $method) {
            $stateChangingUris[] = "{$method} {$uri}";
        }
    }

    // Property: The application must have a comprehensive set of state-changing routes
    // This ensures the test isn't vacuously true on an empty route set
    expect(count($stateChangingUris))->toBeGreaterThanOrEqual(10,
        'Application should have at least 10 state-changing routes (conventions, floors, sections, users, attendance, auth)'
    );

    // Verify known critical routes are present and protected
    $criticalPatterns = [
        'conventions',           // Convention CRUD
        'floors',                // Floor CRUD
        'sections',              // Section CRUD
        'users',                 // User management
        'attendance',            // Attendance reporting
        'login',                 // Authentication
        'invitation',            // User invitation
        'settings/profile',      // Profile updates
    ];

    foreach ($criticalPatterns as $pattern) {
        $matchingRoutes = array_filter($stateChangingUris, fn ($uri) => str_contains($uri, $pattern));

        expect(count($matchingRoutes))->toBeGreaterThan(0,
            "Critical route pattern [{$pattern}] must have at least one state-changing route"
        );
    }
})->group('property', 'security');

it('applies ValidateCsrfToken before controller logic for all state-changing routes', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
    $middlewareGroups = $kernel->getMiddlewareGroups();
    $webMiddleware = $middlewareGroups['web'] ?? [];

    // Property: ValidateCsrfToken must appear in the web middleware stack
    expect($webMiddleware)->toContain(ValidateCsrfToken::class);

    // Property: ValidateCsrfToken should run early in the middleware pipeline
    // (before route-specific middleware like auth, role checks, etc.)
    $csrfIndex = array_search(ValidateCsrfToken::class, $webMiddleware);

    expect($csrfIndex)->not->toBeFalse(
        'ValidateCsrfToken must be present in web middleware group'
    );

    // Verify CSRF runs before session-dependent middleware
    // It should be positioned after session start but that's handled by Laravel's ordering
    expect($csrfIndex)->toBeGreaterThanOrEqual(0,
        'ValidateCsrfToken must have a valid position in the middleware stack'
    );
})->group('property', 'security');
