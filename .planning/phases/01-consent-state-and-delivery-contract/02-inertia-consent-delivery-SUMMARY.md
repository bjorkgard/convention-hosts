# Plan Summary: 02-inertia-consent-delivery

## What Was Built

- Added a top-level shared Inertia `consent` prop in `HandleInertiaRequests` backed by the existing server-side `UserConsentResolver`.
- Defined a shared frontend consent contract type so later prompt/layout work can consume one typed payload from page props.
- Confirmed the existing password-login and two-factor-login responses remain simple redirects and do not need route behavior changes for consent delivery.
- Added feature coverage for authenticated shared-prop delivery, invalid-consent fallback behavior, password login first-delivery behavior, and two-factor completion parity.

## Key Files

- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/types/index.ts`
- `resources/js/types/global.d.ts`
- `tests/Feature/Auth/ConsentSharedPropTest.php`
- `tests/Feature/Auth/ConsentLoginFlowTest.php`

## Contract Decisions

- The shared consent payload is exposed as a top-level `consent` page prop instead of being nested under `auth.user`.
- The frontend contract mirrors the resolver output: `state`, `version`, `allowOptionalStorage`, `decidedAt`, and `updatedAt`.
- Shared delivery rides the normal authenticated Inertia middleware path, so the first redirected destination after login and after two-factor completion receives the same contract without custom bootstrapping.

## Verification Run

- `php artisan test --compact tests/Feature/Auth/ConsentSharedPropTest.php` — pass
- `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php` — pass
- `php artisan test --compact --filter=AuthenticationTest` — pass
- `php artisan test --compact --filter=Consent` — pass
- `npm run types:check` — fail (existing repository-wide TypeScript errors outside this plan's write scope)

## Deviations

- `npm run types:check` still fails because the repository already has unrelated TypeScript issues in existing convention, search, and hook test files outside the plan-owned files. No cross-scope fixes were applied.
