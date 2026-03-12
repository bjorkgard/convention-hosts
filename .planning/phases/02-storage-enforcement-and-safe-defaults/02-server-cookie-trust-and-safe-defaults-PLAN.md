---
wave: 2
depends_on:
  - 01-optional-storage-policy-foundation-PLAN.md
files_modified:
  - app/Http/Middleware/HandleAppearance.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - app/Support/Consent/OptionalStorageRegistry.php
  - resources/views/app.blade.php
  - tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php
  - tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php
autonomous: true
requirements:
  - STOR-01
  - STOR-03
  - STOR-04
  - APPX-03
---

# Plan 02: Server Cookie Trust And Safe Defaults

## Objective

Make Laravel the trust boundary for optional cookies so declined or undecided consent results in essential-only behavior, server-rendered safe defaults, and deterministic forgetting of known optional cookies.

## Must Haves

- Declined or undecided consent never causes Laravel to trust `appearance`, `theme`, or `sidebar_state` cookies as active state.
- Server-rendered appearance and theme fall back to `system` and `default` when optional storage is not allowed.
- The authenticated shell falls back to its built-in sidebar default when optional storage is not allowed.
- Known optional cookies are actively forgotten on responses after decline instead of only being ignored on subsequent reads.
- Essential auth/session cookies remain untouched so sign-in and security flows continue to work.

## Tasks

1. Update [app/Http/Middleware/HandleAppearance.php](/Users/nathanael/Herd/Convention-Hosts/app/Http/Middleware/HandleAppearance.php) to resolve consent through the centralized registry/service and share only consent-allowed values for `appearance` and `theme`, otherwise the agreed safe defaults.
2. Update [app/Http/Middleware/HandleInertiaRequests.php](/Users/nathanael/Herd/Convention-Hosts/app/Http/Middleware/HandleInertiaRequests.php) so `sidebarOpen` is derived from `sidebar_state` only when optional storage is allowed and otherwise falls back to the app default without trusting the request cookie.
3. Extend the server-side registry/helper so middleware can attach forget instructions for the known optional cookies when consent is declined or undecided, while explicitly excluding session, remember-me, CSRF, and other essential cookies from any cleanup path.
4. Update [resources/views/app.blade.php](/Users/nathanael/Herd/Convention-Hosts/resources/views/app.blade.php) to consume consent-aware server values and stop applying `localStorage.theme` as a hydration shortcut when optional storage is disallowed.
5. Ensure Blade still applies system dark mode correctly when the safe fallback appearance is `system`, but does not resurrect previously stored optional theme state after decline.
6. Add feature coverage in [tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php) for:
   - accepted consent still trusts known optional cookies
   - declined consent ignores known optional cookies and returns response cookie-forget instructions for them
   - undecided consent behaves the same as declined for optional cookie trust
   - essential auth/session behavior continues working while optional cookies are denied
7. Add rendering-focused feature coverage in [tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php) for:
   - HTML root attributes using `system` and `default` when optional storage is disallowed
   - Inertia shared props exposing default sidebar state when `sidebar_state` exists but consent does not allow optional storage
   - previously supplied optional cookies no longer influencing rendered output after decline
8. Keep this plan narrowly on server trust, response cleanup, and safe defaults; do not add prompt UI logic or browser localStorage cleanup code here.

## Verification Criteria

- Feature tests prove that only essential cookies remain effective after decline and that known optional cookies are either forgotten or ignored.
- Server-rendered HTML and shared Inertia props use consent-aware safe defaults instead of stale browser preference cookies.
- The implementation makes the cookie trust boundary explicit in Laravel middleware rather than scattering conditionals across unrelated controllers or views.
- No task expands into global auth regression work beyond the essential continuity assertions required by `STOR-01`.

## Automated Verification

- `php artisan test --compact tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`
- `php artisan test --compact tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`
- `php artisan test --compact --filter=Consent`

## Notes For Execution

- If forgetting optional cookies is easiest at middleware response time, keep the behavior centralized there instead of duplicating it in controllers.
- Treat undecided the same as declined for enforcement purposes in this phase because `allowOptionalStorage` is already false for both states.
- Do not couple cookie forgetting to the future Phase 3 prompt endpoint; enforcement must hold for any request made with non-accepted consent.
