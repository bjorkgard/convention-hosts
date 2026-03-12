# Phase 1 Research: Consent State And Delivery Contract

## What Phase 1 Must Achieve

Phase 1 exists to satisfy `CONS-04` in a brownfield Laravel 12 + Inertia + React app:

- one authoritative consent record persisted on `users`
- one server-owned consent policy version
- one shared Inertia contract the authenticated app can trust on every authenticated page

It does not need to enforce storage policy yet, clear old browser state, or ship the prompt UX.

## Recommended Brownfield Seam

The best seam is:

1. persist consent columns directly on `users`
2. add a small Laravel-side consent resolver/presenter that converts raw user fields into a stable app contract
3. share that resolved contract from `app/Http/Middleware/HandleInertiaRequests.php`

Why this seam fits this repo:

- `app/Models/User.php` is already the authenticated authority used by Fortify and Inertia shared props.
- `app/Http/Middleware/HandleInertiaRequests.php` is already the repo-standard place for authenticated shared state (`auth`, `sidebarOpen`, `flash`, `appVersion`).
- `app/Http/Responses/LoginResponse.php` and `app/Http/Responses/TwoFactorLoginResponse.php` already redirect users into authenticated Inertia pages, so the first post-login page can receive consent state without inventing a second transport.

Do not make the React hook authoritative. The current helper in `resources/js/hooks/use-cookie-consent.tsx` is browser-local and cannot satisfy persistence across authenticated sessions.

## Recommended Server Contract

Use a server-owned contract with explicit state and policy metadata. Recommended shape:

```php
[
    'state' => 'undecided' | 'accepted' | 'declined',
    'version' => 1,
    'allowOptionalStorage' => bool,
    'decidedAt' => ?string,
    'updatedAt' => ?string,
]
```

Recommended semantics:

- `version` is the current policy version, not just the stored row value.
- `allowOptionalStorage` is derived server-side from resolved state, so React does not reinterpret rules.
- `decidedAt` is when the user first made the current decision.
- `updatedAt` is when the consent record last changed.
- If the database values are missing, malformed, or version-mismatched, the resolved contract must be `undecided`.

## Persistence Shape On `users`

Phase context already decided that storage belongs directly on `users`, not in a separate consent table. Plan around that.

Recommended fields:

- `consent_state` or `cookie_consent_state`: string/enum-like column with `undecided`, `accepted`, `declined`
- `consent_version` or `cookie_consent_version`: unsigned integer, nullable until a decision is made or defaulted consistently
- `consent_decided_at`: nullable timestamp
- `consent_updated_at`: nullable timestamp

Planning guidance:

- Prefer names without `optional_storage` in the column names. This phase is about consent contract, not enforcement detail.
- Prefer plain string columns plus application validation over a database enum if the team wants easier future evolution. Laravel-side normalization is enough here.
- Treat missing legacy rows as `undecided` at read time.

## Recommended Version Source

Move the version constant out of `resources/js/hooks/use-cookie-consent.tsx` and into Laravel-owned configuration.

Recommended seam:

- `config/consent.php` with `current_version`
- optional enum/value object/constants class used by the resolver

Why:

- the backend becomes the policy source of truth
- tests can override config cheaply
- the Inertia prop can always expose the backend’s current version

Do not keep the version authoritative in TypeScript and mirror it manually in PHP. That recreates drift immediately.

## Exact Repo Integration Points

### 1. Migration seam

Add a new migration after the existing user migrations in `database/migrations/`.

This phase should only add the consent columns to `users`. Do not add a new `consents` table.

### 2. Model seam

Update `app/Models/User.php` for:

- casts for the new timestamps
- optional helper accessors only if they stay thin

Do not bury version invalidation rules directly across multiple model accessors if a resolver/service can centralize them better.

### 3. Contract resolution seam

Add one Laravel-side resolver/presenter, for example:

- `app/Support/Consent/UserConsentResolver.php`
- or `app/Data/Consent/AuthenticatedConsentData.php`

Its responsibility should be:

- read raw user columns
- compare stored version to configured current version
- collapse invalid/missing rows to `undecided`
- derive `allowOptionalStorage`
- serialize timestamps for Inertia

This is the cleanest brownfield seam because the same resolver can later back prompt UX and storage enforcement without duplicating interpretation logic.

### 4. Shared Inertia prop seam

Update `app/Http/Middleware/HandleInertiaRequests.php`.

Recommended shared prop:

```php
'consent' => $request->user()
    ? $resolver->forUser($request->user())
    : null,
```

Why a top-level `consent` prop is the better fit here:

- it matches other app-wide props like `flash` and `appVersion`
- later prompt/layout code can read it without coupling to the serialized `auth.user` object
- it leaves room for future anonymous consent without reshaping `auth`

Planning consequence:

- `resources/js/types/index.ts`
- `resources/js/types/global.d.ts`

will need a shared prop type update in the implementation phase.

### 5. Login/auth flow seam

`app/Http/Responses/LoginResponse.php` and `app/Http/Responses/TwoFactorLoginResponse.php` are relevant because they determine the first authenticated destination.

Current recommendation: do not change redirect behavior in Phase 1.

Why:

- both responses already redirect to authenticated Inertia routes
- `HandleInertiaRequests` will attach the consent contract to those responses automatically
- `CONS-04` needs persistence and delivery, not banner routing logic

The planning note here is simply: the consent contract must be available on the first authenticated page after Fortify login and after two-factor completion.

### 6. Replacement of the current browser-only helper

Current helper:

- `resources/js/hooks/use-cookie-consent.tsx`

Current dependents:

- `resources/js/components/cookie-consent-banner.tsx`
- `resources/js/hooks/use-appearance.tsx`
- `resources/js/hooks/use-theme.tsx`
- `resources/js/components/ui/sidebar.tsx`

Phase 1 should replace its role as the source of truth, not yet fully rework every consumer.

Recommended replacement strategy:

- keep a frontend helper/hook only as a reader of Inertia shared props
- remove the React-owned version constant as authoritative policy
- stop reading consent state from `localStorage`

Do not make Phase 1 responsible for rewriting appearance/theme/sidebar/install-prompt storage behavior. That belongs to Phase 2.

## What Explicitly Should Not Be Done In Phase 1

To avoid scope creep, Phase 1 should not:

- implement the consent banner mount or prompt UX behavior on the authenticated layout
- add the accept/decline write endpoint unless planning proves it is strictly required for contract completeness
- clear `localStorage`, cookies, or install-prompt dismissal state
- rewrite `use-appearance`, `use-theme`, `resources/views/app.blade.php`, or `resources/js/components/install-prompt.tsx` for enforcement
- change `bootstrap/app.php` cookie exceptions yet
- add anonymous consent support
- add settings UI for revisiting consent later

If implementation starts touching `appearance`, `theme`, `sidebar_state`, or `INSTALL_PROMPT_DISMISSED_KEY` behavior, the phase is spilling into `STOR-*` scope.

## Planning Risks And Decisions To Lock Before Execution

The planner should lock these decisions up front:

- exact column names on `users`
- whether `undecided` is stored explicitly or only produced by resolver fallback
- exact serialization format for timestamps in the Inertia prop
- whether the resolver lives as a service, DTO, or value object
- whether Phase 1 includes a minimal update endpoint/action or only read-side contract delivery

Recommendation:

- store `accepted` and `declined` decisions explicitly
- allow the resolver to produce `undecided` for missing/version-mismatched rows
- keep Phase 1 focused on read-side delivery unless write-path work is necessary for a coherent contract plan

That keeps the phase minimal while still enabling later prompt work.

## Validation Architecture

The validation strategy for this phase should center on contract resolution, not browser storage behavior.

Suggested validation layers:

1. Migration/schema validation
   Confirm the `users` table has the consent columns and that `UserFactory` can create users without breaking existing auth tests.

2. Resolver/unit validation
   Add focused tests for the Laravel resolver/presenter covering:
   - no stored consent => `undecided`
   - accepted current version => `accepted`, `allowOptionalStorage=true`
   - declined current version => `declined`, `allowOptionalStorage=false`
   - version mismatch => `undecided`, `allowOptionalStorage=false`
   - malformed/unexpected stored state => `undecided`

3. Shared Inertia contract validation
   Add a feature test against an authenticated route such as `route('conventions.index')` asserting the shared `consent` prop is present and correctly shaped.

4. Auth-flow delivery validation
   Add a feature test around Fortify login that authenticates a user, follows the redirect to the first authenticated page, and asserts the `consent` prop is delivered there.

5. Two-factor parity validation
   If two-factor remains enabled in the test environment, ensure the post-two-factor destination also exposes the same shared contract.

Validation should not require UI banner assertions in this phase. Those belong to Phase 3.

## Actionable Verification Commands

Use repo-native commands:

```bash
php artisan test --compact --filter=AuthenticationTest
php artisan test --compact --filter=RememberMeSessionTest
php artisan test --compact tests/Feature/DashboardTest.php
php artisan test --compact --filter=Consent
npm run types:check
composer lint
```

If route signatures change during implementation, also run:

```bash
php artisan wayfinder:generate --with-form
```

That should not be necessary for a pure shared-prop/server-contract phase unless a new consent endpoint is added.

## Bottom Line For Planning

Plan Phase 1 as a narrow server-contract phase:

- add consent columns on `users`
- define one Laravel-owned consent version source
- resolve consent server-side in one place
- share the resolved contract through Inertia on authenticated pages
- stop treating the current browser helper as authoritative

Anything beyond that is likely Phase 2 or Phase 3 work.
