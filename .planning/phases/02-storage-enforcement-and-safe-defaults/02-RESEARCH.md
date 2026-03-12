# Phase 2 Research: Storage Enforcement And Safe Defaults

## What Phase 2 Must Achieve

Phase 2 is the enforcement layer behind the Phase 1 consent contract. The key outcome is not a new prompt UI. The key outcome is that authenticated app behavior becomes deterministic when `consent.allowOptionalStorage === false`:

- no app-owned optional cookies are treated as active state
- no app-owned optional browser storage writes occur
- known optional keys created earlier are cleared or ignored
- appearance, theme, sidebar, and install-prompt behavior fall back to safe defaults

This phase covers `STOR-01`, `STOR-02`, `STOR-03`, `STOR-04`, and `APPX-03`.

## Repo Facts That Matter

Phase 1 already created the correct server-owned policy signal:

- `app/Support/Consent/UserConsentResolver.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/types/index.ts`

The resolver contract already exposes the only enforcement flag this phase needs:

- `consent.allowOptionalStorage`

The main problem is that optional persistence still bypasses that server signal in several places:

- `resources/js/hooks/use-appearance.tsx`
- `resources/js/hooks/use-theme.tsx`
- `resources/js/components/ui/sidebar.tsx`
- `resources/js/components/install-prompt.tsx`
- `resources/views/app.blade.php`
- `app/Http/Middleware/HandleAppearance.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/hooks/use-cookie-consent.tsx`

`resources/js/hooks/use-cookie-consent.tsx` is now legacy for enforcement purposes. It reads and writes `localStorage` directly via `cookie_consent`, which conflicts with the Phase 1 server-owned consent contract. Planning should treat that file as a migration hazard, not as the source of truth for Phase 2.

## Exact Integration Points

### 1. Appearance persistence

File: `resources/js/hooks/use-appearance.tsx`

Current behavior:

- initializes `appearance` from `localStorage`
- writes `appearance` to `localStorage`
- writes `appearance` cookie when `canSetCookies()` returns true
- seeds `localStorage` with `'system'` on first load

Phase 2 implications:

- seeding `localStorage` on first load violates the safe-default goal when optional storage is unavailable
- `canSetCookies()` currently checks legacy localStorage consent, not the server contract
- appearance must still apply visually when the user changes it during the current page session, but it must not persist after decline

Safe default required by context:

- fallback appearance is `system`
- resolved light/dark mode should still follow `matchMedia('(prefers-color-scheme: dark)')`

Planning note:

- split "apply current appearance to DOM" from "persist appearance"
- the DOM update stays active
- persistence becomes conditional through one centralized policy seam

### 2. Theme persistence

File: `resources/js/hooks/use-theme.tsx`

Current behavior:

- initializes `theme` from `localStorage`
- writes `theme` to `localStorage`
- writes `theme` cookie when `canSetCookies()` returns true
- auto-selects `apple` or `android` on first load based on user agent and persists that result
- reloads the page after updates so SSR HTML gets the cookie-backed `data-theme`

Phase 2 implications:

- device-based auto-selection is optional preference behavior and is outside the agreed safe default
- when optional storage is unavailable, theme must fall back to `'default'`, not `apple` or `android`
- reloading the page after a theme change only makes sense if the new theme is allowed to persist

Safe default required by context:

- fallback theme is app default theme: `'default'`

Planning note:

- remove any write-on-init behavior when consent does not allow optional storage
- treat device heuristic theme selection as optional-state initialization, not essential behavior

### 3. Blade/bootstrap theme fallback

Files:

- `resources/views/app.blade.php`
- `app/Http/Middleware/HandleAppearance.php`

Current behavior:

- Blade root HTML uses `$appearance` and `$theme`
- inline script applies system dark mode immediately when appearance is `system`
- inline script reads `localStorage.getItem('theme')` and applies it before the app hydrates
- `HandleAppearance` always trusts `appearance` and `theme` cookies if present

Phase 2 implications:

- server must stop trusting `appearance` and `theme` cookies when optional storage is disallowed
- Blade must stop reading `localStorage.theme` when optional storage is disallowed
- otherwise a previously stored theme remains visually active after decline even if later writes are blocked

Planning note:

- `HandleAppearance` should resolve consent and share safe defaults when storage is disallowed
- Blade needs a consent-aware boolean or equivalent shared view value so the inline script knows whether localStorage fallback is allowed

### 4. Sidebar persistence

Files:

- `resources/js/components/ui/sidebar.tsx`
- `resources/js/components/app-shell.tsx`
- `app/Http/Middleware/HandleInertiaRequests.php`

Current behavior:

- `SidebarProvider` writes `sidebar_state` cookie on desktop open/close
- `HandleInertiaRequests` always reads `sidebar_state` and shares `sidebarOpen`
- `AppShell` uses `usePage().props.sidebarOpen` as `defaultOpen`

Phase 2 implications:

- client must stop writing `sidebar_state` when optional storage is disallowed
- server must stop reading `sidebar_state` when optional storage is disallowed
- existing `sidebar_state` cookie must be actively cleared after decline

Safe default required by context:

- sidebar falls back to built-in default on each visit
- in current repo, that built-in default is open because `sidebarOpen` defaults to true when no cookie exists

Planning note:

- `HandleInertiaRequests` is the server trust boundary for sidebar persistence
- `SidebarProvider` is the only client write point for `sidebar_state`
- this is the cleanest place to verify both "ignore" and "clear" behavior

### 5. Install-prompt dismissal

Files:

- `resources/js/components/install-prompt.tsx`
- `resources/js/layouts/app/app-sidebar-layout.tsx`

Current behavior:

- dismissal persists in `localStorage['install-prompt-dismissed']`
- modal auto-opens for eligible mobile users when dismissal key is absent
- the key is written on close, on install accepted flow, and on app installed event

Phase 2 implications:

- dismissal persistence must stop when optional storage is disallowed
- the app can still show the prompt and close it during the current session
- the prompt may reappear on a later visit after decline, which is explicitly allowed by the phase context

Safe default required by context:

- no persisted dismissal when optional storage is unavailable

Planning note:

- this component should consume the same centralized optional-storage policy as appearance/theme/sidebar
- do not add a special one-off consent branch here

## Recommended Centralization Strategy

## Policy Shape

Use the Phase 1 server contract as the single authority for enforcement. The plan should not spread `usePage().props.consent.allowOptionalStorage` checks across every hook and component.

Recommended seams:

- one PHP consent-aware optional-cookie registry/service for server-side cookie trust and cookie clearing
- one TS optional-storage policy module for browser reads, writes, and cleanup
- one lightweight hook that exposes the shared Inertia consent contract to React code

Recommended structure:

- `app/Support/Consent/OptionalStorageRegistry.php`
- `resources/js/lib/consent/optional-storage.ts`
- optionally `resources/js/hooks/use-consent.ts` or `resources/js/hooks/use-optional-storage-policy.ts`

### Why this is the right split

The repo has two runtimes:

- PHP decides whether cookies from the request should be trusted and which cookies should be forgotten on the response
- TS decides whether `localStorage` can be read or written and when browser cleanup runs

Trying to centralize everything in only TS or only PHP will fail because both runtimes independently touch optional state.

What should be centralized is:

- the decision rule: optional storage is allowed only when the shared contract says so
- the explicit key registry
- the fallback values
- the cleanup entry point

### What should not remain centralized in `use-cookie-consent.tsx`

That file currently:

- stores consent in localStorage
- exposes `canSetCookies()`
- still acts like client storage is authoritative

That is now the wrong policy seam. Phase 2 planning should either:

- replace its enforcement role entirely, or
- reduce it to Phase 3 prompt wiring only, with enforcement moved elsewhere

The first option is cleaner. The second is acceptable only if appearance/theme/sidebar/install-prompt stop importing it.

## Recommended Browser-Side API

The browser module should own:

- known optional localStorage keys
- known optional client-written cookie keys
- helpers like `readOptionalLocalStorage`, `writeOptionalLocalStorage`, `removeOptionalStorage`, `clearOptionalStorage`, `isOptionalStorageAllowed`
- fallback values for appearance/theme/sidebar install-dismissal decisions

That allows each feature to say:

- read persisted value only if policy allows it
- otherwise use fallback
- when policy becomes disallowed, clear known optional keys and keep working with in-memory defaults

This avoids repeated ad hoc checks in:

- `use-appearance.tsx`
- `use-theme.tsx`
- `sidebar.tsx`
- `install-prompt.tsx`

## Recommended Server-Side API

The PHP registry/service should own:

- the optional cookie names
- `allowsOptionalStorage(?User $user): bool` or equivalent resolver composition
- a helper for forgetting all known optional cookies on a response
- safe-default reads for appearance/theme/sidebar when optional storage is disallowed

That avoids duplicated cookie-name logic in:

- `HandleAppearance`
- `HandleInertiaRequests`
- any later consent controller / response middleware added in Phase 3

## Deterministic Cleanup List

Use one explicit cleanup registry for known app-owned optional keys. Do not do a broad sweep of all cookies or all localStorage keys.

### Known optional cookies in this repo

- `appearance`
- `theme`
- `sidebar_state`

### Known optional localStorage keys in this repo

- `appearance`
- `theme`
- `install-prompt-dismissed`

### Known legacy key that should be treated carefully

- `cookie_consent`

Reason:

- it exists today in `resources/js/hooks/use-cookie-consent.tsx`
- it is not the Phase 1 source of truth anymore
- if Phase 2 leaves it authoritative, storage enforcement can diverge from the server contract

Planning recommendation:

- include `cookie_consent` in the client cleanup registry only if Phase 2 also removes all enforcement dependencies on it
- otherwise mark it as a migration blocker for Phase 3 and do not let new Phase 2 code depend on it

## Essential vs Optional In This Repo

### Essential for Phase 2

These are inside the "must keep working after decline" bucket:

- Laravel auth/session cookies needed for authenticated navigation
- CSRF/session security behavior
- existing authenticated Inertia responses
- server-owned consent fields stored on `users`

These are required so the app remains usable and secure after decline.

### Optional for Phase 2

These are the explicit app-owned optional states that Phase 2 should enforce:

- appearance persistence
- theme persistence
- sidebar open/closed persistence
- install prompt dismissal persistence

These are the only optional states named in current repo code and in the phase context.

### Scope Guardrail

Phase 2 should not expand into:

- consent prompt UX
- settings UI for changing consent later
- anonymous consent flows
- broad cleanup of unknown third-party storage

### Important boundary call: `remember_web_*`

The repo has existing remember-me coverage in `tests/Feature/RememberMeSessionTest.php`, and the login page still exposes a `remember` checkbox in `resources/js/pages/auth/login.tsx`.

That cookie is not part of the explicit Phase 2 context list. It is also not an app-owned preference key like appearance/theme/sidebar/install-dismissal.

Planning recommendation:

- treat `remember_web_*` as an explicit scope question, not silent Phase 2 work
- if product/legal interpretation of `STOR-02` says it is non-essential, that needs a separate decision because it changes authenticated login behavior before the Phase 3 prompt is shown
- if no new product decision is made, keep Phase 2 scoped to the app-owned optional preference storage already identified in context

Without that clarification, Phase 2 can still satisfy the agreed implementation context but may leave a requirements interpretation gap around remember-me.

## Safe Defaults To Lock Before Planning

The plan should lock these defaults so implementation and validation stay deterministic:

- appearance fallback: `system`
- resolved light/dark fallback: current OS preference
- theme fallback: `default`
- sidebar fallback: open (`true`)
- install prompt dismissal fallback: not persisted

The important distinction is:

- the app may still react in-memory during the current visit
- it must not persist optional preference state when optional storage is disallowed

## Sequencing Recommendation

The phase plan will be easier to execute safely if it follows this order:

1. Introduce the registry/policy seams before changing feature behavior.
2. Stop server-side trust of optional cookies in `HandleAppearance` and `HandleInertiaRequests`.
3. Refactor browser persistence helpers to go through the centralized TS policy module.
4. Update bootstrap behavior in `app.tsx` and `app.blade.php` so initial load honors the same policy.
5. Add cleanup behavior for known optional cookies/localStorage keys when consent becomes disallowed.
6. Add tests around the new seams before touching any prompt UX.

This order minimizes temporary states where one runtime enforces policy but the other still leaks old optional state.

## Bootstrap Risk To Plan Around

`resources/js/app.tsx` currently calls:

- `initializeTheme()` from `use-appearance.tsx`
- `initializeTheme()` from `use-theme.tsx`

outside React and outside any `usePage()` access.

That means bootstrap code currently has no direct access to the shared Inertia consent prop.

The plan needs one of these solutions:

- move bootstrap into a small mounted React component that can read `usePage<PageProps>().props.consent`, or
- pass the initial consent contract from `createInertiaApp(... setup)` into the init functions explicitly

The first option is cleaner because it avoids a split brain between bootstrap code and page-level code.

## Validation Architecture

Validation should be split by runtime boundary.

### PHP validation

Goal:

- prove the server stops trusting optional cookies when consent disallows storage
- prove safe defaults are shared instead
- prove optional cookies are queued for removal through one centralized list

Recommended coverage:

- unit test for the new PHP optional-cookie registry/service
- feature test that declined consent + request cookies `appearance`, `theme`, `sidebar_state` still yields safe defaults
- feature test that `sidebarOpen` is `true` even when `sidebar_state=false` is sent and consent is declined
- feature test that response headers forget optional cookies when consent is declined

Likely test targets:

- `tests/Unit/Support/...`
- `tests/Feature/Auth/...`

### TS validation

Goal:

- prove browser optional storage writes are blocked when policy disallows them
- prove known optional localStorage keys are cleared deterministically
- prove fallback behavior remains usable without persisted storage

Recommended coverage:

- unit tests for the TS optional-storage module
- hook tests for `use-appearance.tsx` fallback and non-persisting updates
- hook tests for `use-theme.tsx` fallback to `'default'` and blocked persistence
- component test for `install-prompt.tsx` showing non-persisted dismissal behavior
- targeted test for sidebar write suppression if extracting cookie-write logic into a testable helper

### Integration contract validation

Goal:

- prove both runtimes obey the same consent decision

Recommended checks:

- one feature or component-level flow where consent is declined and previously existing optional state no longer changes rendered behavior on a fresh visit
- one test asserting Blade bootstrap does not reactivate `localStorage.theme` when optional storage is disallowed

This is the highest-risk regression area because the current repo has both server cookie reads and pre-hydration Blade localStorage reads.

## Actionable Verification Commands

Use repo-native commands during planning and execution:

### Backend

```bash
php artisan test --compact
php artisan test --compact tests/Feature/Auth/ConsentSharedPropTest.php
php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php
php artisan test --compact tests/Feature/RememberMeSessionTest.php
```

### Frontend

```bash
npm test
npx vitest run resources/js/hooks/__tests__/use-cookie-consent.test.ts
npx vitest run resources/js/components/__tests__/cookie-consent-banner.test.tsx
```

### Quality gates

```bash
composer lint
npm run lint
npm run format
npm run build
npm run types:check
```

Known repo state from `.planning/STATE.md`:

- `npm run types:check` currently fails due to pre-existing unrelated TypeScript issues outside this consent scope

Planning should account for that so Phase 2 verification is not blocked by unrelated TS failures.

## Planning Recommendations

The phase plan should explicitly decide:

- where the central PHP optional-cookie registry lives
- where the central TS optional-storage module lives
- whether bootstrap moves into React or receives initial consent from `app.tsx` setup
- whether `cookie_consent` is fully deprecated during Phase 2 or only stripped from enforcement paths
- whether `remember_web_*` is explicitly out of scope for this phase or requires a product decision before implementation

If those decisions are made upfront, the implementation work stays narrow and Phase 2 remains a storage-enforcement phase rather than becoming a prompt or auth redesign.
