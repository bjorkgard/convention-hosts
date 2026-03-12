# Phase 3 Research: Authenticated Prompt Experience

**Researched:** 2026-03-12
**Scope:** Integrate the cookie consent prompt into the shared authenticated shell so undecided users must choose immediately after login, using the existing server-owned consent contract and replacing the legacy localStorage banner flow.

## Summary

Phase 3 should be implemented as a shared authenticated-shell concern, not a page concern and not a login-flow rewrite. The codebase already has the two hard dependencies in place:

- a server-owned consent contract shared on every authenticated Inertia response via `HandleInertiaRequests`
- a server-owned write action in `RecordUserConsentAction`

The missing pieces are:

- a real authenticated POST endpoint that calls `RecordUserConsentAction`
- a shell-mounted React prompt that renders when `consent.state === 'undecided'`
- removal of the legacy localStorage-driven `CookieConsentBanner` and `useCookieConsent` hook from the active authenticated path

The current Fortify login and two-factor flows already land on authenticated Inertia pages that include the shared consent contract on first delivery. That means the consent prompt can appear immediately after login without adding a dedicated post-login consent screen.

## Shared Authenticated Shell Mount Point

### Primary mount

The correct mount point is [`resources/js/layouts/app/app-sidebar-layout.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/layouts/app/app-sidebar-layout.tsx). This is the shared wrapper used by authenticated app pages through [`resources/js/layouts/app-layout.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/layouts/app-layout.tsx).

Current structure:

- `AppShell variant="sidebar"`
- `AppSidebar`
- `AppContent variant="sidebar"`
- `AppSidebarHeader`
- page children
- mobile-only bottom install prompt container

Why this is the right place:

- all authenticated product pages shown in Phase 3 scope use `AppLayout`
- it already hosts bottom-mounted shared UI (`InstallPrompt`) in the authenticated main content area
- it sits after the header and page content, so the prompt can persist across authenticated navigation without page duplication

### Mounting constraints

- The current bottom prompt container is `md:hidden`, so `InstallPrompt` is only shown on mobile there.
- The consent prompt must not inherit that mobile-only restriction.
- The prompt should live in the main content column, not in the sidebar, so it is visible on first authenticated render even if the sidebar is collapsed or closed.
- `AppContent` is a flex column and already uses `mt-auto` for bottom anchoring. A new prompt can reuse the same pattern, but the planner should avoid accidental competition between the consent prompt and install prompt spacing on small screens.

### Practical shell options

Most consistent option:

- render `AuthenticatedConsentPrompt` inside `AppContent`, after `{children}`, near the existing install prompt wrapper

Likely layout shape:

- consent prompt wrapper always available
- install prompt remains below or adjacent on mobile only

This keeps the consent prompt persistent across page changes while preserving the existing shell structure.

## Server-Owned Consent Write Path

### Existing write seam

[`app/Actions/Consent/RecordUserConsentAction.php`](/Users/nathanael/Herd/Convention-Hosts/app/Actions/Consent/RecordUserConsentAction.php) already owns the persistence logic for:

- accepted vs declined validation
- policy version stamping
- `consent_decided_at` preservation semantics
- `consent_updated_at` refresh

This should remain the only place that mutates user consent fields.

### Missing HTTP seam

There is currently no controller or route exposing authenticated consent writes. `routes/web.php` contains no consent route yet.

Recommended shape:

- new authenticated controller dedicated to consent writes
- single POST endpoint under authenticated middleware, for example `/consent`
- thin controller calling `RecordUserConsentAction`
- dedicated `FormRequest` validating `state` to `accepted|declined`
- redirect back to the current page so Inertia receives fresh shared props

This matches the repo’s existing patterns:

- thin controllers
- validation in `FormRequest`
- server-owned business logic in action classes

### Likely implementation path

Recommended files:

- `app/Http/Controllers/ConsentController.php`
- `app/Http/Requests/RecordConsentRequest.php` or `app/Http/Requests/Consent/RecordConsentRequest.php`
- `routes/web.php` authenticated route

Likely controller behavior:

- authorize authenticated user implicitly via `auth` middleware
- call `RecordUserConsentAction::execute($request->user(), $request->validated('state'))`
- return `back()` or `to_route(...)` only if a page-specific redirect is required

`back()` is more reusable because the shell prompt can appear on many authenticated pages and should disappear after the normal redirect-refresh cycle.

## Current Login Redirect And First Authenticated Inertia Response

### Redirect chain

Login still uses Fortify’s normal redirect response customization:

- [`app/Http/Responses/LoginResponse.php`](/Users/nathanael/Herd/Convention-Hosts/app/Http/Responses/LoginResponse.php)
- [`app/Http/Responses/TwoFactorLoginResponse.php`](/Users/nathanael/Herd/Convention-Hosts/app/Http/Responses/TwoFactorLoginResponse.php)

Both currently:

- inspect the authenticated user’s convention count
- redirect to `conventions.show` when there is exactly one convention
- otherwise redirect to `config('fortify.home', '/conventions')`
- finally call `redirect()->intended($redirectTo)`

Phase 3 should not replace this redirect chain with a consent screen. The prompt must show on the first authenticated destination produced by this existing logic.

### First authenticated Inertia delivery

[`app/Http/Middleware/HandleInertiaRequests.php`](/Users/nathanael/Herd/Convention-Hosts/app/Http/Middleware/HandleInertiaRequests.php) shares:

- `auth.user`
- `consent`
- `sidebarOpen`

The consent contract is resolved on every request via [`app/Support/Consent/UserConsentResolver.php`](/Users/nathanael/Herd/Convention-Hosts/app/Support/Consent/UserConsentResolver.php).

Important behavior:

- missing user, invalid state, or stale version collapses to `undecided`
- `allowOptionalStorage` is `true` only for `accepted`
- undecided already behaves like declined for storage trust

### Verified behavior already covered

[`tests/Feature/Auth/ConsentLoginFlowTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentLoginFlowTest.php) already proves:

- password login first delivers an authenticated Inertia response with shared `consent`
- two-factor completion does the same

This is a strong planning constraint: Phase 3 does not need special login plumbing to get consent state into React on first authenticated paint.

## Replacing The Legacy localStorage Banner

### Current legacy pieces

- [`resources/js/components/cookie-consent-banner.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/cookie-consent-banner.tsx)
- [`resources/js/hooks/use-cookie-consent.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-cookie-consent.tsx)
- legacy tests:
  - [`resources/js/components/__tests__/cookie-consent-banner.test.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/__tests__/cookie-consent-banner.test.tsx)
  - [`resources/js/hooks/__tests__/use-cookie-consent.test.ts`](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/__tests__/use-cookie-consent.test.ts)

These are explicitly legacy now:

- they write consent state to `localStorage`
- they model consent as `{ accepted: boolean, version: number }`
- they bypass the authenticated shared Inertia contract
- they are incompatible with the Phase 1 and Phase 2 server-owned model

### Replacement guidance

Do not extend these files.

Instead:

- remove them from the active authenticated consent path
- replace them with a component that reads from `useConsent()`
- submit accept/decline through the server route

The new component should treat the shared contract as truth:

- render when `consent.state === 'undecided'`
- hide when `consent.state` becomes `accepted` or `declined` after redirect refresh

### Compatibility note

Because Phase 2 already enforces optional-storage denial when consent is not accepted, the new prompt does not need to preserve any of the old localStorage compatibility behavior. Reusing the old hook would actively undermine the architecture.

## Reusable Patterns For Authenticated Inertia POST + Refresh

Two established client patterns exist in this repo.

### Pattern A: shell or event action with `router.post`

Examples:

- [`resources/js/components/conventions/attendance-report-banner.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/conventions/attendance-report-banner.tsx)
- [`resources/js/components/conventions/user-row.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/conventions/user-row.tsx)

Use when:

- the UI is not a traditional form
- there are one-click actions
- the page should refresh through normal Inertia navigation

This is a good fit for consent because the prompt has only two buttons and no rich form state.

Likely usage:

- `router.post(record.url(), { state: 'accepted' })`
- `router.post(record.url(), { state: 'declined' })`

Useful options:

- `preserveScroll: true`
- local pending state via `onStart` / `onFinish` or component state

### Pattern B: Wayfinder `.form()` with `<Form>`

Examples:

- [`resources/js/pages/auth/login.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/pages/auth/login.tsx)
- [`resources/js/pages/settings/profile.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/pages/settings/profile.tsx)

Use when:

- the component is semantically a form
- validation errors must map onto named fields
- the code benefits from standard form helpers

This can also work for consent, but it is heavier than needed for a two-button prompt unless the planner specifically wants the new endpoint consumed through a tiny `<Form>`.

### Recommendation

Prefer `router.post(...)` for the prompt component itself, but still generate a Wayfinder action for the route so URLs are not hardcoded.

That gives:

- consistent route typing
- no custom client fetch code
- immediate redirect + shared-prop refresh behavior
- minimal UI state surface

## Existing Test Coverage And Likely New Tests

### Existing backend coverage to preserve

Consent contract and write logic:

- [`tests/Unit/Actions/Consent/RecordUserConsentActionTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Unit/Actions/Consent/RecordUserConsentActionTest.php)
- [`tests/Unit/Support/UserConsentResolverTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Unit/Support/UserConsentResolverTest.php)
- [`tests/Feature/Auth/ConsentSharedPropTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentSharedPropTest.php)
- [`tests/Feature/Auth/ConsentLoginFlowTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentLoginFlowTest.php)

Storage enforcement and safe defaults:

- [`tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php)
- [`tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php)
- [`resources/js/components/__tests__/install-prompt-consent.test.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/__tests__/install-prompt-consent.test.tsx)
- [`resources/js/components/ui/__tests__/sidebar-consent.test.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/ui/__tests__/sidebar-consent.test.tsx)

These already prove that undecided/declined state is safe for storage policy. Phase 3 tests should focus on prompt integration and server round-trip, not re-proving Phase 2.

### Legacy frontend tests likely to remove or rewrite

- [`resources/js/components/__tests__/cookie-consent-banner.test.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/__tests__/cookie-consent-banner.test.tsx)
- [`resources/js/hooks/__tests__/use-cookie-consent.test.ts`](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/__tests__/use-cookie-consent.test.ts)

These are tied to the superseded localStorage architecture and should not survive unchanged.

### Likely new tests later

Backend feature tests:

- authenticated POST to consent endpoint records `accepted`
- authenticated POST to consent endpoint records `declined`
- consent POST redirects back and refreshed Inertia props no longer show `undecided`
- unauthenticated POST is rejected by auth middleware
- invalid `state` fails validation

Frontend/Vitest tests:

- shell prompt renders when shared `consent.state` is `undecided`
- shell prompt does not render for `accepted`
- shell prompt does not render for `declined`
- clicking `Accept all` posts the accepted state
- clicking `Decline` posts the declined state
- prompt buttons share equal prominence at the component level if design assertions are maintained

Feature-level integration later in Phase 4:

- following login, undecided users land on the first authenticated page with the prompt visible
- after accepting or declining, the prompt disappears on the refreshed authenticated response
- with one convention and with many conventions, the prompt still appears on the first destination
- after two-factor completion, the prompt still appears on the first destination

## Architecture Patterns

### Use the Inertia shared contract as the only read source

Use [`resources/js/hooks/use-consent.ts`](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-consent.ts) everywhere in authenticated React code. Do not derive prompt state from browser storage.

### Keep writes server-owned

Write consent through:

- authenticated route
- controller
- `FormRequest`
- `RecordUserConsentAction`

This matches the existing Laravel architecture and keeps versioning semantics centralized.

### Let redirect refresh remove the prompt

Do not manually mutate a client-side consent cache. The clean pattern is:

1. render prompt from shared props
2. submit POST
3. redirect back
4. let `HandleInertiaRequests` share the updated consent contract
5. prompt naturally stops rendering

This keeps prompt visibility coupled to the same contract that already drives storage enforcement.

## Don't Hand-Roll

- Do not add a separate client-only consent store for authenticated pages.
- Do not keep `localStorage` as a fallback write path for accept/decline.
- Do not add a dedicated post-login consent route or full-page gate in this phase.
- Do not duplicate storage cleanup logic in the prompt component; Phase 2 already owns that policy.
- Do not hardcode frontend URLs; use generated Wayfinder routes/actions.

## Common Pitfalls

- Mounting the prompt inside a single page instead of the shared shell will miss other authenticated destinations and violate the phase goal.
- Reusing the legacy `use-cookie-consent` hook will split source of truth between browser storage and Laravel.
- Posting consent and then manually hiding the prompt without a refresh risks stale `consent` props and inconsistent storage policy state.
- Returning JSON from the consent write endpoint instead of redirecting will work against the existing Inertia navigation pattern unless the client is rewritten around manual reloads.
- Forgetting to regenerate Wayfinder after adding a route will break the frontend import pattern.
- Sharing the prompt only on mobile because it is colocated with the current install prompt wrapper would violate the requirement that the first authenticated destination shows it generally.

## Validation Architecture

Phase 3 itself is primarily an integration phase, so validation should be split by seam.

### Backend seam validation

Cover the new consent POST route with feature tests that verify:

- auth is required
- only `accepted` and `declined` are allowed
- `RecordUserConsentAction` effects are visible after redirect
- redirect target preserves the current authenticated page flow

### Shared-prop validation

Rely on the existing `ConsentLoginFlowTest` and `ConsentSharedPropTest` patterns to confirm:

- the first authenticated Inertia response still includes consent
- updated consent props are visible on subsequent authenticated responses

### Shell component validation

Use Vitest with mocked `usePage().props` and mocked `router.post` to verify:

- prompt visibility rules
- action payloads
- transient pending-button behavior if added

This should replace the old banner tests instead of layering on top of them.

## Planner Constraints

- The prompt must live in the authenticated shell, not in auth pages and not in a specific authenticated page.
- The login redirect chain should stay intact; prompt visibility must be achieved through the first destination response.
- The consent read model is already solved by the shared Inertia `consent` prop; do not invent another read path.
- The consent write model should be a new authenticated HTTP route around `RecordUserConsentAction`, not browser persistence.
- Existing Phase 2 storage enforcement means undecided users are already operating under safe defaults; Phase 3 is mainly UI integration plus write-path completion.
- Legacy localStorage consent code is architectural debt now and should be removed or replaced, not extended.
- Route generation must be refreshed after adding the consent endpoint because frontend code should use Wayfinder imports.
- `npm run types:check` is known to have unrelated pre-existing failures per project state, so planner verification should prefer targeted tests unless that broader issue is explicitly addressed.

## Files Inspected

Planning docs:

- [`03-CONTEXT.md`](/Users/nathanael/Herd/Convention-Hosts/.planning/phases/03-authenticated-prompt-experience/03-CONTEXT.md)
- [`REQUIREMENTS.md`](/Users/nathanael/Herd/Convention-Hosts/.planning/REQUIREMENTS.md)
- [`STATE.md`](/Users/nathanael/Herd/Convention-Hosts/.planning/STATE.md)
- [`AGENTS.md`](/Users/nathanael/Herd/Convention-Hosts/AGENTS.md)

Auth flow and shared contract:

- [`app/Http/Responses/LoginResponse.php`](/Users/nathanael/Herd/Convention-Hosts/app/Http/Responses/LoginResponse.php)
- [`app/Http/Responses/TwoFactorLoginResponse.php`](/Users/nathanael/Herd/Convention-Hosts/app/Http/Responses/TwoFactorLoginResponse.php)
- [`app/Providers/FortifyServiceProvider.php`](/Users/nathanael/Herd/Convention-Hosts/app/Providers/FortifyServiceProvider.php)
- [`app/Http/Middleware/HandleInertiaRequests.php`](/Users/nathanael/Herd/Convention-Hosts/app/Http/Middleware/HandleInertiaRequests.php)
- [`app/Support/Consent/UserConsentResolver.php`](/Users/nathanael/Herd/Convention-Hosts/app/Support/Consent/UserConsentResolver.php)

Consent persistence and storage enforcement:

- [`app/Actions/Consent/RecordUserConsentAction.php`](/Users/nathanael/Herd/Convention-Hosts/app/Actions/Consent/RecordUserConsentAction.php)
- [`app/Support/Consent/OptionalStorageRegistry.php`](/Users/nathanael/Herd/Convention-Hosts/app/Support/Consent/OptionalStorageRegistry.php)
- [`app/Http/Middleware/HandleAppearance.php`](/Users/nathanael/Herd/Convention-Hosts/app/Http/Middleware/HandleAppearance.php)
- [`app/Models/User.php`](/Users/nathanael/Herd/Convention-Hosts/app/Models/User.php)
- [`routes/web.php`](/Users/nathanael/Herd/Convention-Hosts/routes/web.php)
- [`routes/settings.php`](/Users/nathanael/Herd/Convention-Hosts/routes/settings.php)

Authenticated shell and client patterns:

- [`resources/js/layouts/app/app-sidebar-layout.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/layouts/app/app-sidebar-layout.tsx)
- [`resources/js/layouts/app-layout.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/layouts/app-layout.tsx)
- [`resources/js/components/app-shell.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/app-shell.tsx)
- [`resources/js/components/app-content.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/app-content.tsx)
- [`resources/js/components/install-prompt.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/install-prompt.tsx)
- [`resources/js/hooks/use-consent.ts`](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-consent.ts)
- [`resources/js/types/index.ts`](/Users/nathanael/Herd/Convention-Hosts/resources/js/types/index.ts)
- [`resources/js/components/conventions/attendance-report-banner.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/conventions/attendance-report-banner.tsx)
- [`resources/js/components/conventions/user-row.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/conventions/user-row.tsx)
- [`resources/js/pages/auth/login.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/pages/auth/login.tsx)
- [`resources/js/pages/auth/two-factor-challenge.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/pages/auth/two-factor-challenge.tsx)

Legacy consent code to replace:

- [`resources/js/components/cookie-consent-banner.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/cookie-consent-banner.tsx)
- [`resources/js/hooks/use-cookie-consent.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-cookie-consent.tsx)

Relevant tests:

- [`tests/Feature/Auth/ConsentLoginFlowTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentLoginFlowTest.php)
- [`tests/Feature/Auth/ConsentSharedPropTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentSharedPropTest.php)
- [`tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php)
- [`tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php)
- [`tests/Unit/Actions/Consent/RecordUserConsentActionTest.php`](/Users/nathanael/Herd/Convention-Hosts/tests/Unit/Actions/Consent/RecordUserConsentActionTest.php)
- [`resources/js/components/__tests__/cookie-consent-banner.test.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/__tests__/cookie-consent-banner.test.tsx)
- [`resources/js/hooks/__tests__/use-cookie-consent.test.ts`](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/__tests__/use-cookie-consent.test.ts)
- [`resources/js/components/__tests__/install-prompt-consent.test.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/__tests__/install-prompt-consent.test.tsx)
- [`resources/js/components/ui/__tests__/sidebar-consent.test.tsx`](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/ui/__tests__/sidebar-consent.test.tsx)
