# Phase 4 Research: Verification And Regression Coverage

**Researched:** 2026-03-12
**Scope:** Prove the authenticated consent flow across real Fortify login redirects, optional storage enforcement, and essential authenticated continuity after decline.

## Summary

Phase 4 should stay backend-heavy and treat the remaining risk as an integration problem across Fortify redirects, shared Inertia props, middleware-enforced cookie trust, and post-login session continuity. The app already has most primitives covered in isolation:

- `tests/Feature/Auth/ConsentLoginFlowTest.php` proves the shared consent contract reaches the first authenticated Inertia response after password login and two-factor completion.
- `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php` proves accepted, declined, and undecided consent affect optional cookie trust and response cookie forgetting.
- `tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php` proves safe defaults for HTML root attributes and `sidebarOpen`.
- `resources/js/components/__tests__/authenticated-consent-prompt.test.tsx` proves the prompt renders for `undecided` and posts the server-owned decision.
- targeted frontend tests already exist for `theme`, `appearance`, `sidebar_state`, and install-prompt dismissal.

Phase 4 does not need a browser-suite rewrite. It needs a clean regression split that proves:

- `VERI-01`: the real first authenticated destination receives `undecided` consent on the Fortify redirect paths that actually exist in production, so the shared shell will show the prompt there
- `VERI-02`: app-owned optional state stays inactive when consent is declined or undecided, while at least one accepted path proves the policy is conditional rather than globally disabled
- `VERI-03`: declining consent does not break login, authenticated follow-up navigation, or the session/XSRF plumbing required for later authenticated requests

## Recommended Test Split

### VERI-01: Immediate post-login prompt proof

Use `tests/Feature/Auth/ConsentLoginFlowTest.php` as the primary backend file and extend it rather than creating a parallel login regression file.

Why this file should be extended:

- it already follows the real Fortify `login.store` and `two-factor.login.store` paths
- it already uses `followingRedirects()` to reach the first authenticated Inertia response
- it is already the canonical place for consent-on-login behavior

Recommended additions:

- add password-login coverage for both redirect shapes:
  - exactly one convention -> `conventions/show`
  - anything else in practice for this scope -> `conventions/index`
- add two-factor coverage for both redirect shapes:
  - exactly one convention -> `conventions/show`
  - multiple or no conventions -> `conventions/index`
- in each case assert:
  - authenticated user is established
  - final Inertia component matches the real Fortify destination
  - shared `consent.state` is `undecided`
  - shared `consent.allowOptionalStorage` is `false`

Important constraint:

- backend feature tests cannot directly prove a hydrated React prompt is visible in the browser because the prompt is client-rendered inside the authenticated shell
- the backend proof should therefore establish the real redirect destination plus the exact `undecided` contract delivered to that shell
- prompt visibility itself remains supported by frontend React coverage

Recommended supporting frontend addition:

- add a new layout-level test file, `resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx`
- use it to prove the shared authenticated shell mounts `AuthenticatedConsentPrompt` when page props contain `consent.state = 'undecided'`
- also prove the shell does not mount it when consent is accepted or declined

That gives the required split:

- backend proves the real Fortify redirect and authenticated Inertia payload
- frontend proves that payload causes prompt visibility in the actual shared shell mount point, not only in the isolated prompt component

### VERI-02: Decline enforcement regression surface

Keep the backend core in `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php` and `tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`, then extend the targeted frontend tests already covering the app-owned optional storage boundaries.

Why this split is best:

- cookie trust and response-cookie forgetting are server concerns and already belong in feature tests
- localStorage persistence and cleanup are client concerns and already have focused Vitest coverage
- the named regression surface is small and already centralized in `OptionalStorageRegistry` and `resources/js/lib/consent/optional-storage.ts`

Recommended backend extensions:

- extend `ConsentOptionalCookieEnforcementTest.php` with one helper or dataset for consent state plus expected cookie trust behavior to reduce repeated setup
- keep accepted, declined, and undecided assertions in this file because they are all cross-request HTTP behavior
- leave `ConsentSafeDefaultRenderingTest.php` focused on rendered defaults and `sidebarOpen`

Recommended frontend extensions:

- extend `resources/js/hooks/__tests__/use-appearance-consent.test.tsx`
  - add one accepted-consent test proving appearance writes persist when storage is allowed
  - it currently covers only denied behavior
- keep `resources/js/hooks/__tests__/use-theme-consent.test.tsx`
  - it already has the accepted happy path and the declined path
- extend `resources/js/components/ui/__tests__/sidebar-consent.test.tsx`
  - add one accepted-consent assertion showing `sidebar_state` is written when allowed
- extend `resources/js/components/__tests__/install-prompt-consent.test.tsx`
  - add one accepted-consent assertion showing dismissal persists when allowed

Do not add a broad new frontend storage file for this requirement. The existing files already align with the actual ownership boundaries:

- `use-appearance` owns appearance persistence
- `use-theme` owns theme persistence
- `SidebarProvider` owns `sidebar_state`
- `InstallPrompt` owns dismissal persistence

### VERI-03: Essential authenticated continuity after decline

Add one new dedicated backend feature file: `tests/Feature/Auth/ConsentSessionContinuityTest.php`.

Why a new file is warranted:

- the suite already has one continuity assertion inside `ConsentOptionalCookieEnforcementTest.php`, but it is mixed into storage enforcement behavior
- Phase 4 needs a requirement-shaped proof for session continuity, not just one extra assertion hidden inside a storage test
- this requirement is cross-request and backend-driven, so a dedicated feature file keeps the boundary clear

Recommended scenarios for the new file:

- declined user can log in successfully through `login.store` and remains authenticated after redirect
- declined user can navigate to a second authenticated page after login and still remain authenticated
- declined user receives essential cookies needed for security/session continuity on login response:
  - session cookie
  - `XSRF-TOKEN`
- declined user can make a later authenticated POST request under the same session, such as `POST /consent` to switch from declined to accepted, proving later authenticated interactions still work

This file should stay focused on continuity, not reopen optional storage policy details already covered elsewhere.

## Existing Tests To Extend

- `tests/Feature/Auth/ConsentLoginFlowTest.php`
  - extend to cover both first-destination shapes and use `undecided` users for prompt proof
- `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`
  - keep accepted/declined/undecided cookie trust and forgetting here
  - consider extracting small local helpers or a Pest dataset for repeated cookie expectations
- `tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`
  - keep safe HTML defaults and `sidebarOpen` assertions here
- `resources/js/hooks/__tests__/use-appearance-consent.test.tsx`
  - add accepted persistence coverage
- `resources/js/hooks/__tests__/use-theme-consent.test.tsx`
  - already has the right accepted-vs-declined contrast; likely only minor alignment changes if naming is standardized
- `resources/js/components/ui/__tests__/sidebar-consent.test.tsx`
  - add accepted write coverage
- `resources/js/components/__tests__/install-prompt-consent.test.tsx`
  - add accepted dismissal persistence coverage

## New Test Files To Add

- `tests/Feature/Auth/ConsentSessionContinuityTest.php`
  - dedicated `VERI-03` requirement file
- `resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx`
  - targeted shell integration file for prompt visibility and storage cleanup behavior at the real mount point

## Real Fortify Login And Two-Factor Coverage

The app already uses custom Fortify response classes:

- `app/Http/Responses/LoginResponse.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`

Both implement the same redirect rule:

- one convention -> `route('conventions.show', $id)`
- otherwise -> `config('fortify.home', '/conventions')`

That makes the correct Phase 4 proof very specific:

- do not fake the first destination with a direct authenticated GET
- do not add a synthetic consent screen
- do not treat only `conventions/index` as the canonical first destination

Instead, extend `ConsentLoginFlowTest.php` so it follows the real Fortify redirects and asserts the first authenticated Inertia response for:

- password login on `conventions/show`
- password login on `conventions/index`
- two-factor completion on `conventions/show`
- two-factor completion on `conventions/index`

Prompt visibility should be inferred from the authenticated shell contract plus verified by the shell-level Vitest file, because the backend response does not server-render the React prompt markup itself.

## Integration Boundaries For Optional State

Phase 4 should verify the known app-owned optional state at the boundaries where it becomes active state.

### `appearance`

Backend boundary:

- `app/Http/Middleware/HandleAppearance.php`
- `app/Support/Consent/OptionalStorageRegistry.php`

Frontend boundary:

- `resources/js/hooks/use-appearance.tsx`

Regression proof:

- declined and undecided ignore persisted `appearance`
- accepted can persist and later read it
- denied consent may still allow in-session visual change, but that change must not persist

### `theme`

Backend boundary:

- `app/Http/Middleware/HandleAppearance.php`
- `app/Support/Consent/OptionalStorageRegistry.php`

Frontend boundary:

- `resources/js/hooks/use-theme.tsx`

Regression proof:

- declined and undecided force safe default theme for trusted output
- accepted can persist the chosen theme
- denied consent must not trigger persisted theme writes or reload-driven persistence

### `sidebar_state`

Backend boundary:

- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Support/Consent/OptionalStorageRegistry.php`

Frontend boundary:

- `resources/js/components/ui/sidebar.tsx`
- `resources/js/components/app-shell.tsx`

Regression proof:

- declined and undecided do not let `sidebar_state` influence shared `sidebarOpen`
- accepted can write and later reuse `sidebar_state`
- denied consent still leaves the shell usable via fallback `sidebarOpen = true`

### install-prompt dismissal

Frontend-only boundary:

- `resources/js/components/install-prompt.tsx`
- `resources/js/lib/consent/optional-storage.ts`

Regression proof:

- declined and undecided do not persist dismissal
- when consent becomes disallowed, a previously stored dismissal is removed and ignored
- accepted can persist dismissal, proving the deny-path is conditional rather than globally disabled

## Essential Session And XSRF Continuity After Decline

This requirement should be asserted with backend feature tests, but with one important limitation in mind:

- PHPUnit feature tests are strong for cookie presence, authenticated follow-up requests, and redirect/session continuity
- they are weak for proving a true browser-enforced CSRF challenge because the test client is not acting like a real browser runtime and the app already accepts normal feature-test POSTs without explicit token choreography

Because of that, the most reliable continuity assertions are:

- after declined login, the response includes the session cookie
- after declined login, the response includes `XSRF-TOKEN`
- a later authenticated GET still succeeds under the same session
- a later authenticated POST still succeeds under the same session

That is enough for planning because the goal is consent-related continuity, not a separate CSRF middleware audit.

## Reusable Helpers And Patterns

### Strong reusable assets

- `Tests\Helpers\ConventionTestHelper`
  - use it for single-convention redirect setup instead of hand-building convention pivots
- `database/factories/UserFactory.php`
  - reuse `withTwoFactor()` for two-factor login coverage instead of ad hoc `forceFill()` setup
- `User::factory()->withTwoFactor()`
  - already used in `ConsentLoginFlowTest.php` and is the cleanest path for two-factor login coverage
- existing local helper pattern inside `ConsentOptionalCookieEnforcementTest.php`
  - `responseCookieNames()`
  - `knownOptionalResponseCookieNames()`
  - `rootHtmlTag()`
  - these can stay local or be extracted only if Phase 4 adds enough duplication to justify it

### Existing tests that already model useful boundaries

- `tests/Feature/Auth/AuthenticationTest.php`
  - baseline password login behavior
- `tests/Feature/Auth/TwoFactorChallengeTest.php`
  - baseline two-factor challenge rendering
- `tests/Feature/RememberMeSessionTest.php`
  - useful reference for asserting essential auth cookies without mixing consent concerns

### Dataset situation

There is no established shared Pest dataset pattern in the current consent/auth files.

Recommendation:

- keep datasets local to the files that need them
- the best candidate is a small local dataset in `ConsentLoginFlowTest.php` for first-destination shape
- a second small local dataset in `ConsentOptionalCookieEnforcementTest.php` for consent-state-to-expected-cookie-policy mapping is reasonable

Do not add a global shared dataset file for this phase. The surface area is too small to justify more abstraction.

## Validation Architecture

Phase 4 validation should be requirement-shaped and layered, with backend feature tests providing the primary proof and targeted Vitest files only filling the browser-behavior gaps the backend cannot express cleanly.

### Layer 1: backend feature proof

Primary files:

- `tests/Feature/Auth/ConsentLoginFlowTest.php`
- `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`
- `tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`
- `tests/Feature/Auth/ConsentSessionContinuityTest.php` (new)

What this layer proves:

- real Fortify password and two-factor redirect behavior
- first authenticated Inertia payload contains the expected consent contract
- optional cookie trust and forgetting are enforced across requests
- safe server-rendered defaults hold when optional storage is disallowed
- session and XSRF cookie continuity remain intact after decline

### Layer 2: targeted frontend integration proof

Primary files:

- `resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx` (new)
- `resources/js/components/__tests__/authenticated-consent-prompt.test.tsx`
- `resources/js/components/__tests__/install-prompt-consent.test.tsx`
- `resources/js/components/ui/__tests__/sidebar-consent.test.tsx`
- `resources/js/hooks/__tests__/use-appearance-consent.test.tsx`
- `resources/js/hooks/__tests__/use-theme-consent.test.tsx`

What this layer proves:

- the authenticated shell actually mounts the prompt for `undecided`
- deny/undecided paths do not persist app-owned optional state
- accepted paths still persist at least one value on each optional-state surface where Phase 4 needs conditional proof

### Requirement mapping

- `VERI-01`
  - backend: `ConsentLoginFlowTest.php`
  - frontend support: `app-sidebar-layout-consent.test.tsx`, `authenticated-consent-prompt.test.tsx`
- `VERI-02`
  - backend: `ConsentOptionalCookieEnforcementTest.php`, `ConsentSafeDefaultRenderingTest.php`
  - frontend support: appearance/theme/sidebar/install-prompt targeted tests
- `VERI-03`
  - backend: `ConsentSessionContinuityTest.php`

### Practical validation commands

- `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php`
- `php artisan test --compact tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`
- `php artisan test --compact tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`
- `php artisan test --compact tests/Feature/Auth/ConsentSessionContinuityTest.php`
- `npx vitest run resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx`
- `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx`
- `npx vitest run resources/js/components/__tests__/install-prompt-consent.test.tsx`
- `npx vitest run resources/js/components/ui/__tests__/sidebar-consent.test.tsx`
- `npx vitest run resources/js/hooks/__tests__/use-appearance-consent.test.tsx`
- `npx vitest run resources/js/hooks/__tests__/use-theme-consent.test.tsx`

## Files Inspected

Planning inputs:

- `AGENTS.md`
- `.planning/phases/04-verification-and-regression-coverage/04-CONTEXT.md`
- `.planning/REQUIREMENTS.md`
- `.planning/STATE.md`

Backend auth and consent flow:

- `app/Http/Responses/LoginResponse.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Middleware/HandleAppearance.php`
- `app/Support/Consent/UserConsentResolver.php`
- `app/Support/Consent/OptionalStorageRegistry.php`
- `routes/web.php`

Frontend authenticated-shell and storage surfaces:

- `resources/js/layouts/app/app-sidebar-layout.tsx`
- `resources/js/components/authenticated-consent-prompt.tsx`
- `resources/js/components/install-prompt.tsx`
- `resources/js/components/ui/sidebar.tsx`
- `resources/js/components/app-shell.tsx`
- `resources/js/hooks/use-consent.ts`
- `resources/js/hooks/use-appearance.tsx`
- `resources/js/hooks/use-theme.tsx`
- `resources/js/lib/consent/optional-storage.ts`

Existing tests:

- `tests/Feature/Auth/ConsentLoginFlowTest.php`
- `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`
- `tests/Feature/Auth/ConsentRecordEndpointTest.php`
- `tests/Feature/Auth/ConsentSharedPropTest.php`
- `tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/TwoFactorChallengeTest.php`
- `tests/Feature/RememberMeSessionTest.php`
- `tests/Helpers/ConventionTestHelper.php`
- `database/factories/UserFactory.php`
- `resources/js/components/__tests__/authenticated-consent-prompt.test.tsx`
- `resources/js/components/__tests__/install-prompt-consent.test.tsx`
- `resources/js/components/ui/__tests__/sidebar-consent.test.tsx`
- `resources/js/hooks/__tests__/use-appearance-consent.test.tsx`
- `resources/js/hooks/__tests__/use-theme-consent.test.tsx`
- `resources/js/lib/consent/__tests__/optional-storage.test.ts`
