---
wave: 2
depends_on:
  - 01-server-consent-contract-PLAN.md
files_modified:
  - app/Http/Middleware/HandleInertiaRequests.php
  - resources/js/types/index.ts
  - resources/js/types/global.d.ts
  - tests/Feature/Auth/ConsentSharedPropTest.php
  - tests/Feature/Auth/ConsentLoginFlowTest.php
autonomous: true
requirements:
  - CONS-04
---

# Plan 02: Shared Inertia Consent Delivery

## Objective

Expose the resolved server consent contract on every authenticated Inertia response, including the first authenticated destination after login, so the frontend can trust one shared consent payload instead of browser-only state.

## Must Haves

- Shared consent data is delivered from Laravel on authenticated Inertia responses through one stable prop contract.
- The shared contract includes at least `state`, `version`, and `allowOptionalStorage`, with timestamps serialized consistently if exposed.
- The first authenticated page after password login receives the same consent contract without custom frontend bootstrapping.
- Two-factor completion, if applicable in existing auth flow tests, reaches a page with the same shared contract.
- This plan does not introduce consent banner rendering, accept/decline UX behavior, or Phase 2 storage enforcement.

## Tasks

1. Update [app/Http/Middleware/HandleInertiaRequests.php](/Users/nathanael/Herd/Convention-Hosts/app/Http/Middleware/HandleInertiaRequests.php) to share a top-level authenticated `consent` prop sourced from the server resolver rather than from client storage.
2. Define the exact prop shape used by the frontend and update shared TypeScript types in [resources/js/types/index.ts](/Users/nathanael/Herd/Convention-Hosts/resources/js/types/index.ts) and [resources/js/types/global.d.ts](/Users/nathanael/Herd/Convention-Hosts/resources/js/types/global.d.ts) so later prompt/layout work consumes one typed contract.
3. Review [app/Http/Responses/LoginResponse.php](/Users/nathanael/Herd/Convention-Hosts/app/Http/Responses/LoginResponse.php) and [app/Http/Responses/TwoFactorLoginResponse.php](/Users/nathanael/Herd/Convention-Hosts/app/Http/Responses/TwoFactorLoginResponse.php) only to confirm the existing redirect flow still lands on an authenticated Inertia page that will receive shared consent props; avoid redirect-behavior changes unless required to preserve delivery.
4. Add feature coverage in [tests/Feature/Auth/ConsentSharedPropTest.php](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentSharedPropTest.php) asserting an authenticated Inertia route includes the resolved shared `consent` prop with the expected shape and fallback semantics.
5. Add auth-flow coverage in [tests/Feature/Auth/ConsentLoginFlowTest.php](/Users/nathanael/Herd/Convention-Hosts/tests/Feature/Auth/ConsentLoginFlowTest.php) asserting login reaches an authenticated response path that exposes the shared consent contract on first delivery.
6. If the existing test environment exercises two-factor login, extend or add parity assertions so the post-two-factor destination exposes the same shared contract.

## Verification Criteria

- Authenticated Inertia responses expose one server-derived consent contract and do not require `localStorage` to determine state.
- The first authenticated destination after login receives the contract through the normal Inertia response cycle.
- Feature tests cover both steady-state authenticated access and the login delivery seam identified in the phase validation strategy.
- No implementation step changes banner UX, adds consent-management settings, or rewrites appearance/theme/install-prompt persistence behavior.

## Automated Verification

- `php artisan test --compact tests/Feature/Auth/ConsentSharedPropTest.php`
- `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php`
- `php artisan test --compact --filter=AuthenticationTest`
- `php artisan test --compact --filter=Consent`
- `npm run types:check`

## Notes For Execution

- Prefer a top-level `consent` shared prop over burying the contract inside `auth.user`, which keeps the transport stable for later anonymous/authenticated expansion.
- If no route or response changes are needed for delivery, keep login response files untouched and document that the existing redirect seam is sufficient.
