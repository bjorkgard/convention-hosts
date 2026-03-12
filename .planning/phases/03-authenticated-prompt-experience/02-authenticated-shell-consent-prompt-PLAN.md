---
phase: 03-authenticated-prompt-experience
plan: 02
type: execute
wave: 2
depends_on:
  - 01
files_modified:
  - resources/js/components/authenticated-consent-prompt.tsx
  - resources/js/layouts/app/app-sidebar-layout.tsx
  - resources/js/components/cookie-consent-banner.tsx
  - resources/js/hooks/use-cookie-consent.tsx
  - resources/js/components/__tests__/authenticated-consent-prompt.test.tsx
  - resources/js/components/__tests__/cookie-consent-banner.test.tsx
  - resources/js/hooks/__tests__/use-cookie-consent.test.ts
autonomous: true
requirements:
  - CONS-01
  - CONS-02
  - CONS-03
  - APPX-01
  - APPX-02
user_setup: []
must_haves:
  truths:
    - "Undecided authenticated users see a consent prompt in the shared authenticated shell on their first authenticated destination."
    - "The prompt shows only `Accept all` and `Decline` with equal prominence."
    - "Accepting or declining posts through the server-owned consent route and the prompt disappears after the refreshed Inertia response."
    - "Authenticated prompt visibility and dismissal are driven by the shared server consent contract instead of a browser-owned consent record."
  artifacts:
    - path: resources/js/components/authenticated-consent-prompt.tsx
      provides: Server-backed authenticated consent prompt component
      min_lines: 30
    - path: resources/js/layouts/app/app-sidebar-layout.tsx
      provides: Shared authenticated shell mount point for the consent prompt
      contains: AuthenticatedConsentPrompt
    - path: resources/js/components/__tests__/authenticated-consent-prompt.test.tsx
      provides: Prompt rendering and submission tests
      min_lines: 30
    - path: resources/js/components/cookie-consent-banner.tsx
      provides: Legacy banner compatibility shim or replacement path
      min_lines: 5
    - path: resources/js/hooks/use-cookie-consent.tsx
      provides: Compatibility-only hook behavior, no active authenticated source of truth
      min_lines: 10
  key_links:
    - from: resources/js/layouts/app/app-sidebar-layout.tsx
      to: resources/js/components/authenticated-consent-prompt.tsx
      via: shared AppContent mount
      pattern: AuthenticatedConsentPrompt
    - from: resources/js/components/authenticated-consent-prompt.tsx
      to: resources/js/actions/App/Http/Controllers/ConsentController.ts
      via: router.post using generated Wayfinder action
      pattern: ConsentController
    - from: resources/js/components/authenticated-consent-prompt.tsx
      to: resources/js/hooks/use-consent.ts
      via: shared consent visibility check
      pattern: useConsent
---

<objective>
Mount a server-backed consent prompt in the shared authenticated shell so undecided users see it on the first authenticated destination, can choose `Accept all` or `Decline` with equal prominence, and stop using the legacy browser-owned banner path.

Purpose: finish the authenticated prompt experience without changing login redirects or reopening Phase 2 storage enforcement.
Output: new authenticated shell prompt component, shared shell mount, legacy banner replacement shim, and targeted frontend test coverage.
</objective>

<execution_context>
@/Users/nathanael/.codex/get-shit-done/workflows/execute-plan.md
@/Users/nathanael/.codex/get-shit-done/templates/summary.md
</execution_context>

<context>
@.planning/PROJECT.md
@.planning/ROADMAP.md
@.planning/STATE.md
@.planning/REQUIREMENTS.md
@.planning/phases/03-authenticated-prompt-experience/03-CONTEXT.md
@.planning/phases/03-authenticated-prompt-experience/03-RESEARCH.md
@.planning/phases/02-storage-enforcement-and-safe-defaults/03-client-storage-gating-and-cleanup-SUMMARY.md
@resources/js/layouts/app/app-sidebar-layout.tsx
@resources/js/hooks/use-consent.ts
@resources/js/components/cookie-consent-banner.tsx
@resources/js/hooks/use-cookie-consent.tsx
</context>

<tasks>

<task type="auto">
  <name>Task 1: Build the server-backed authenticated prompt component</name>
  <files>resources/js/components/authenticated-consent-prompt.tsx, resources/js/components/__tests__/authenticated-consent-prompt.test.tsx</files>
  <action>Create a new authenticated-shell prompt component that reads `consent.state` from `useConsent()`, renders only for `undecided`, shows exactly `Accept all` and `Decline` with equal visual weight, and posts the chosen `state` through `router.post` using the generated Wayfinder consent action from Wave 1. Add a narrow pending state to prevent duplicate submissions, keep the page behind the prompt usable, and avoid introducing any modal overlay or client-owned consent cache. Cover rendering, hiding for accepted/declined, posting correct payloads, and pending-state duplicate blocking in Vitest.</action>
  <verify>npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx</verify>
  <done>The new prompt component is fully driven by the shared Inertia consent contract, submits only `accepted` or `declined` through the server route, and has targeted component tests for its visible behavior.</done>
</task>

<task type="auto">
  <name>Task 2: Mount the prompt in the shared authenticated shell and preserve shell usability</name>
  <files>resources/js/layouts/app/app-sidebar-layout.tsx</files>
  <action>Mount the new prompt inside `AppContent` after page children so it appears on every authenticated page without requiring sidebar interaction. Keep the prompt outside the `md:hidden` install-prompt wrapper so it remains visible on desktop and mobile, and adjust spacing so the consent prompt and existing mobile install prompt can coexist without overlapping or pushing the consent UI into the sidebar. Keep this implementation non-blocking and shell-scoped rather than introducing page-specific or login-specific wiring.</action>
  <verify>npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx</verify>
  <done>The shared authenticated shell always mounts the new prompt in the main content area, and undecided users can see it across authenticated navigation on both mobile and desktop layouts.</done>
</task>

<task type="auto">
  <name>Task 3: Retire the legacy localStorage consent path without breaking compatibility tests</name>
  <files>resources/js/components/cookie-consent-banner.tsx, resources/js/hooks/use-cookie-consent.tsx, resources/js/components/__tests__/cookie-consent-banner.test.tsx, resources/js/hooks/__tests__/use-cookie-consent.test.ts</files>
  <action>Replace the old localStorage-backed banner with the smallest compatibility-safe path that prevents dual-banner rendering in the authenticated shell. Either convert `cookie-consent-banner.tsx` into a thin compatibility wrapper around the new prompt or into an unmounted deprecated shim, and reduce `use-cookie-consent.tsx` to compatibility-only status so authenticated flows no longer treat it as the active write path. Update the existing banner and hook tests so they stop asserting the legacy authenticated localStorage architecture while still protecting against accidental reintroduction of the old banner path.</action>
  <verify>npx vitest run resources/js/components/__tests__/cookie-consent-banner.test.tsx && npx vitest run resources/js/hooks/__tests__/use-cookie-consent.test.ts</verify>
  <done>The authenticated shell no longer mounts or depends on the legacy localStorage banner path, and the remaining compatibility tests reflect that narrower role instead of asserting browser-owned consent writes.</done>
</task>

</tasks>

<verification>
Before declaring plan complete:
- [ ] `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx`
- [ ] `npx vitest run resources/js/components/__tests__/cookie-consent-banner.test.tsx`
- [ ] `npx vitest run resources/js/hooks/__tests__/use-cookie-consent.test.ts`
- [ ] `npm run lint`
</verification>

<success_criteria>

- All tasks completed
- All verification checks pass
- No errors or warnings introduced
- The prompt is mounted in the shared authenticated shell and visible whenever consent is undecided
- The prompt offers only equal-prominence `Accept all` and `Decline` actions and posts through the server-owned route
- The refreshed Inertia consent contract naturally hides the prompt after a decision
- The legacy localStorage banner path is no longer the authenticated source of truth
</success_criteria>

<output>
After completion, create `.planning/phases/03-authenticated-prompt-experience/02-authenticated-shell-consent-prompt-SUMMARY.md`
</output>
