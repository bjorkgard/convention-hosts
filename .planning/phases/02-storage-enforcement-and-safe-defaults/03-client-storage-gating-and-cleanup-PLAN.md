---
wave: 2
depends_on:
  - 01-optional-storage-policy-foundation-PLAN.md
files_modified:
  - resources/js/hooks/use-appearance.tsx
  - resources/js/hooks/use-theme.tsx
  - resources/js/components/ui/sidebar.tsx
  - resources/js/components/install-prompt.tsx
  - resources/js/layouts/app/app-sidebar-layout.tsx
  - resources/js/hooks/__tests__/use-appearance-consent.test.tsx
  - resources/js/hooks/__tests__/use-theme-consent.test.tsx
  - resources/js/components/ui/__tests__/sidebar-consent.test.tsx
  - resources/js/components/__tests__/install-prompt-consent.test.tsx
autonomous: true
requirements:
  - STOR-02
  - STOR-03
  - STOR-04
  - APPX-03
---

# Plan 03: Client Storage Gating And Cleanup

## Objective

Apply the centralized optional-storage policy to the authenticated React shell so appearance, theme, sidebar persistence, and install-prompt dismissal stop writing optional state before acceptance, clear known keys after decline, and remain usable with safe in-memory defaults.

## Must Haves

- `use-appearance` applies appearance changes in the current session but does not seed or persist optional state when storage is disallowed.
- `use-theme` falls back to `default`, does not auto-persist device-specific themes without consent, and does not force reloads solely for disallowed persistence.
- `SidebarProvider` stops writing `sidebar_state` when optional storage is disallowed and still works with the app’s default open state.
- `InstallPrompt` can still open and close in-session, but dismissal is not persisted when optional storage is disallowed.
- Known optional localStorage keys are cleared promptly when consent becomes disallowed after previously being accepted.

## Tasks

1. Refactor [resources/js/hooks/use-appearance.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-appearance.tsx) to separate DOM application from persistence so:
   - initial state uses the centralized fallback when optional storage is disallowed
   - no first-load `localStorage` seeding occurs without consent
   - updates still change the current document appearance in-session
   - persistence writes route only through the new optional-storage policy helper
2. Refactor [resources/js/hooks/use-theme.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/use-theme.tsx) so:
   - the default theme is `default` when optional storage is disallowed
   - iOS/Android auto-selection is treated as optional initialization and skipped without consent
   - cookie/localStorage writes are centralized through the policy helper
   - hard reload behavior only occurs when a persisted server-visible theme change is actually allowed
3. Update [resources/js/components/ui/sidebar.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/ui/sidebar.tsx) to stop direct `sidebar_state` cookie writes and to clear that cookie through the centralized helper when consent is not accepted.
4. Update [resources/js/components/install-prompt.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/install-prompt.tsx) to route dismissal reads and writes through the new policy helper so later visits may reshow the prompt when optional persistence is unavailable.
5. Review [resources/js/layouts/app/app-sidebar-layout.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/layouts/app/app-sidebar-layout.tsx) only as needed to preserve safe default shell behavior with the new `sidebarOpen` semantics; avoid broader layout redesign.
6. Add a consent-change cleanup path so when the shared contract transitions from allowed to disallowed in the authenticated app, the known optional keys for appearance, theme, sidebar, and install-prompt dismissal are removed immediately rather than remaining active until the next visit.
7. Add Vitest coverage in [resources/js/hooks/__tests__/use-appearance-consent.test.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/__tests__/use-appearance-consent.test.tsx) for:
   - fallback to `system` when storage is disallowed
   - no localStorage/cookie writes on init without consent
   - in-session updates still applying to the DOM without persistence
8. Add Vitest coverage in [resources/js/hooks/__tests__/use-theme-consent.test.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/hooks/__tests__/use-theme-consent.test.tsx) for:
   - fallback to `default` without consent
   - no device-heuristic persisted theme initialization without consent
   - persisted writes and reload only when storage is allowed
9. Add Vitest coverage in [resources/js/components/ui/__tests__/sidebar-consent.test.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/ui/__tests__/sidebar-consent.test.tsx) for:
   - no `sidebar_state` cookie writes when storage is disallowed
   - cleanup of an existing `sidebar_state` cookie after consent becomes disallowed
   - shell behavior staying usable with the default open state
10. Add Vitest coverage in [resources/js/components/__tests__/install-prompt-consent.test.tsx](/Users/nathanael/Herd/Convention-Hosts/resources/js/components/__tests__/install-prompt-consent.test.tsx) for:
   - dismissal not persisting when storage is disallowed
   - previously stored dismissal being cleared/ignored after consent becomes disallowed
   - current-session close behavior still working

## Verification Criteria

- Browser tests prove that optional localStorage and client-written cookies are not created before acceptance and are cleared or ignored after decline.
- Appearance, theme, sidebar, and install-prompt flows continue functioning with in-memory safe defaults when optional persistence is unavailable.
- The implementation relies on one shared optional-storage helper rather than feature-specific consent branches.
- Execution does not broaden into repo-wide TypeScript cleanup; targeted Vitest suites are the gate for this plan unless touched files introduce a new local type failure.

## Automated Verification

- `npx vitest run resources/js/hooks/__tests__/use-appearance-consent.test.tsx`
- `npx vitest run resources/js/hooks/__tests__/use-theme-consent.test.tsx`
- `npx vitest run resources/js/components/ui/__tests__/sidebar-consent.test.tsx`
- `npx vitest run resources/js/components/__tests__/install-prompt-consent.test.tsx`
- `npm test -- --run`

## Notes For Execution

- Keep cleanup keyed to the explicit allowlist from Plan 01; do not implement a generic `localStorage.clear()` strategy.
- If a browser-side reaction to consent changes needs a single app-level effect, place it near the authenticated shell rather than duplicating per component.
- Leave Blade bootstrap ownership to Plan 02 in this wave; Plan 03 should consume the consent-aware server defaults without modifying [resources/views/app.blade.php](/Users/nathanael/Herd/Convention-Hosts/resources/views/app.blade.php).
- Do not add prompt rendering, accept/decline actions, or first-login UX in this plan; this phase only enforces storage behavior once the server contract is present.
