---
wave: 1
depends_on:
  - ../01-consent-state-and-delivery-contract/01-server-consent-contract-PLAN.md
  - ../01-consent-state-and-delivery-contract/02-inertia-consent-delivery-PLAN.md
files_modified:
  - app/Support/Consent/OptionalStorageRegistry.php
  - app/Http/Middleware/HandleAppearance.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - resources/js/lib/consent/optional-storage.ts
  - resources/js/hooks/use-consent.ts
  - resources/js/hooks/use-cookie-consent.tsx
  - resources/js/types/index.ts
  - resources/js/types/global.d.ts
  - tests/Unit/Support/Consent/OptionalStorageRegistryTest.php
  - resources/js/lib/consent/__tests__/optional-storage.test.ts
autonomous: true
requirements:
  - STOR-02
  - STOR-04
  - APPX-03
---

# Plan 01: Optional Storage Policy Foundation

## Objective

Create the single enforcement seam for optional browser persistence so later Phase 2 work can route all cookie and localStorage reads, writes, and cleanup through a consent-aware policy instead of ad hoc checks.

## Must Haves

- One explicit registry owns the known optional cookie names and localStorage keys covered in this phase.
- React/browser code reads the shared Inertia consent contract instead of treating `resources/js/hooks/use-cookie-consent.tsx` as authoritative.
- Browser-side helpers expose safe fallback behavior for appearance, theme, sidebar, and install-prompt persistence without writing optional state on initialization.
- Server-side code has a reusable way to decide whether optional cookies may be trusted and to enumerate which cookies must be forgotten after decline.
- This plan does not add consent prompt UI, accept/decline button flows, or Phase 4 end-to-end verification work.

## Tasks

1. Add a PHP registry such as [app/Support/Consent/OptionalStorageRegistry.php](/Users/nathanael/Herd/Convention-Hosts/app/Support/Consent/OptionalStorageRegistry.php) that defines:
   - the known optional cookie names for this phase (`appearance`, `theme`, `sidebar_state`)
   - the matching optional browser storage keys used by the authenticated shell (`appearance`, `theme`, `install-prompt-dismissed`)
   - safe defaults for server-trusted appearance, theme, and sidebar values
   - a narrow API for `allowsOptionalStorage`, `optionalCookieNames`, and response cookie-forget orchestration
2. Add a browser policy module at [resources/js/lib/consent/optional-storage.ts](/Users/nathanael/Herd/Convention-Hosts/resources/js/lib/consent/optional-storage.ts) that centralizes:
   - `isOptionalStorageAllowed` from the shared Inertia `consent` prop
   - read/write/remove helpers for optional localStorage keys
   - optional cookie write/remove helpers for client-managed cookies
   - one cleanup entry point that clears the known optional keys covered by the phase
   - safe fallback constants for appearance, theme, and install-prompt behavior
3. Add a lightweight React seam such as [resources/js/hooks/use-consent.ts](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-consent.ts) so feature hooks and components do not each reimplement `usePage().props.consent` parsing.
4. Update shared TypeScript contracts in [resources/js/types/index.ts](/Users/nathanael/Herd/Convention-Hosts/resources/js/types/index.ts) and [resources/js/types/global.d.ts](/Users/nathanael/Herd/Convention-Hosts/resources/js/types/global.d.ts) only as needed so the new policy helpers can consume typed consent and fallback data without broad type cleanup.
5. Reduce [resources/js/hooks/use-cookie-consent.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-cookie-consent.tsx) to a non-authoritative compatibility surface or remove its enforcement exports from active imports, ensuring no Phase 2 storage code still depends on `canSetCookies()` or client-owned consent records.
6. Add PHP unit coverage in [tests/Unit/Support/Consent/OptionalStorageRegistryTest.php](/Users/nathanael/Herd/Convention-Hosts/tests/Unit/Support/Consent/OptionalStorageRegistryTest.php) for:
   - accepted consent allows optional storage
   - declined and undecided consent deny optional storage
   - cookie forget lists include all known optional cookies and exclude essential auth/session cookies
   - fallback values remain the agreed safe defaults when storage is not allowed
7. Add browser-unit coverage in [resources/js/lib/consent/__tests__/optional-storage.test.ts](/Users/nathanael/Herd/Convention-Hosts/resources/js/lib/consent/__tests__/optional-storage.test.ts) for:
   - reads returning fallbacks when optional storage is disallowed
   - writes becoming no-ops when optional storage is disallowed
   - cleanup removing the exact allowlisted keys and no broader dynamic sweep
8. Confirm the foundation leaves Phase 3 free to wire prompt actions later without reintroducing localStorage-based consent decisions.

## Verification Criteria

- There is one explicit PHP registry and one explicit TS policy module that own the Phase 2 optional key list and decision rule.
- No appearance, theme, sidebar, or install-prompt implementation needs to import `canSetCookies()` after this foundation lands.
- Safe defaults are represented centrally and are test-covered before feature-specific enforcement work begins.
- Automated verification uses targeted PHP and Vitest coverage for the new policy seam and does not expand this phase into repo-wide `npm run types:check` stabilization.

## Automated Verification

- `php artisan test --compact tests/Unit/Support/Consent/OptionalStorageRegistryTest.php`
- `npx vitest run resources/js/lib/consent/__tests__/optional-storage.test.ts`
- `php artisan test --compact --filter=Consent`

## Notes For Execution

- Keep the registry allowlist explicit and phase-scoped; do not turn this into generic sweeping of arbitrary browser keys.
- The existing repo-wide `npm run types:check` instability is known and unrelated; fix only type errors introduced by touched Phase 2 files.
- If `use-cookie-consent.tsx` is still needed for Phase 3 prompt wiring, leave it as a thin UI-facing compatibility layer rather than the enforcement authority.
