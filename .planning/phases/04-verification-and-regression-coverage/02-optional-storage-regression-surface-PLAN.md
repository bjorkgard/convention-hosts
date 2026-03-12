---
phase: 04-verification-and-regression-coverage
plan: 02
type: execute
wave: 2
depends_on:
  - "01"
files_modified:
  - tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php
  - tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php
  - resources/js/hooks/__tests__/use-appearance-consent.test.tsx
  - resources/js/hooks/__tests__/use-theme-consent.test.tsx
  - resources/js/components/ui/__tests__/sidebar-consent.test.tsx
  - resources/js/components/__tests__/install-prompt-consent.test.tsx
autonomous: true
requirements:
  - VERI-02
user_setup: []
must_haves:
  truths:
    - "Declined and undecided consent do not let `appearance`, `theme`, or `sidebar_state` become active server-visible state."
    - "Declined and undecided consent do not let app-owned optional browser persistence remain active for appearance, theme, sidebar state, or install-prompt dismissal."
    - "At least one accepted-consent path is asserted on each owned regression surface so the enforcement proof is conditional rather than globally disabled."
  artifacts:
    - path: tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php
      provides: Feature coverage for accepted, declined, and undecided cookie trust or forgetting behavior
      min_lines: 50
    - path: tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php
      provides: Feature coverage for safe rendered defaults when optional storage is disallowed
      min_lines: 30
    - path: resources/js/hooks/__tests__/use-appearance-consent.test.tsx
      provides: Appearance consent gating and accepted persistence proof
      min_lines: 30
    - path: resources/js/hooks/__tests__/use-theme-consent.test.tsx
      provides: Theme consent gating and accepted persistence proof
      min_lines: 30
    - path: resources/js/components/ui/__tests__/sidebar-consent.test.tsx
      provides: Sidebar state consent gating and accepted persistence proof
      min_lines: 25
    - path: resources/js/components/__tests__/install-prompt-consent.test.tsx
      provides: Install prompt dismissal consent gating and accepted persistence proof
      min_lines: 30
  key_links:
    - from: tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php
      to: app/Http/Middleware/HandleAppearance.php
      via: server-side optional cookie trust assertions
      pattern: appearance
    - from: tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php
      to: app/Http/Middleware/HandleInertiaRequests.php
      via: safe default sidebar and shared prop assertions
      pattern: sidebarOpen
    - from: resources/js/hooks/__tests__/use-appearance-consent.test.tsx
      to: resources/js/hooks/use-appearance.tsx
      via: persistence gating assertions
      pattern: allowOptionalStorage
    - from: resources/js/components/ui/__tests__/sidebar-consent.test.tsx
      to: resources/js/components/ui/sidebar.tsx
      via: cookie write and cleanup assertions
      pattern: sidebar_state
    - from: resources/js/components/__tests__/install-prompt-consent.test.tsx
      to: resources/js/components/install-prompt.tsx
      via: dismissal persistence assertions
      pattern: install-prompt-dismissed
---

<objective>
Lock `VERI-02` with a narrow regression surface that proves the app's known optional state stays inactive after decline or while undecided, while still proving accepted consent enables that same state on the owned boundaries.

Purpose: protect the highest-risk integration seams from Phase 2 without reopening a generic browser-storage audit.
Output: strengthened backend feature coverage for optional cookie trust and safe defaults, plus targeted Vitest additions for the owned client persistence boundaries.
</objective>

<execution_context>
@/Users/nathanael/.codex/get-shit-done/workflows/execute-plan.md
@/Users/nathanael/.codex/get-shit-done/templates/summary.md
</execution_context>

<context>
@.planning/ROADMAP.md
@.planning/STATE.md
@.planning/REQUIREMENTS.md
@.planning/phases/04-verification-and-regression-coverage/04-CONTEXT.md
@.planning/phases/04-verification-and-regression-coverage/04-RESEARCH.md
@tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php
@tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php
@app/Http/Middleware/HandleAppearance.php
@app/Http/Middleware/HandleInertiaRequests.php
@app/Support/Consent/OptionalStorageRegistry.php
@resources/js/hooks/__tests__/use-appearance-consent.test.tsx
@resources/js/hooks/__tests__/use-theme-consent.test.tsx
@resources/js/components/ui/__tests__/sidebar-consent.test.tsx
@resources/js/components/__tests__/install-prompt-consent.test.tsx
</context>

<tasks>

<task type="auto">
  <name>Task 1: Strengthen backend regression coverage for conditional cookie trust and safe defaults</name>
  <files>tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php, tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php</files>
  <action>Keep the backend proof in the existing consent feature files and extend them rather than creating a new generic regression suite. In `ConsentOptionalCookieEnforcementTest`, factor repeated setup into a small local helper or dataset if it reduces duplication, then assert accepted, declined, and undecided behavior on the same known optional cookie surface so the tests prove enforcement is conditional. In `ConsentSafeDefaultRenderingTest`, keep the focus on rendered defaults and shared `sidebarOpen` behavior when storage is disallowed. Do not pull session continuity into this plan; that belongs to the dedicated `VERI-03` file.</action>
  <verify>php artisan test --compact tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php</verify>
  <done>The backend regression tests prove `appearance`, `theme`, and `sidebar_state` are trusted only when consent is accepted and are ignored or forgotten when consent is declined or undecided.</done>
</task>

<task type="auto">
  <name>Task 2: Extend targeted frontend consent tests only where the owned persistence boundaries need the accepted-path contrast</name>
  <files>resources/js/hooks/__tests__/use-appearance-consent.test.tsx, resources/js/hooks/__tests__/use-theme-consent.test.tsx, resources/js/components/ui/__tests__/sidebar-consent.test.tsx, resources/js/components/__tests__/install-prompt-consent.test.tsx</files>
  <action>Use the existing focused Vitest files instead of adding a broad new storage suite. Add the missing accepted-consent assertions where needed so each owned surface shows both denied cleanup or ignore behavior and an accepted happy path: `use-appearance` should prove writes persist when allowed; `use-theme` should stay aligned with the same accepted-versus-denied contract; `sidebar-consent` should prove `sidebar_state` writes when allowed and is removed when disallowed; `install-prompt-consent` should prove dismissal persists when allowed and is ignored or cleared when disallowed. Keep these tests boundary-specific and do not broaden them into page-level UI regression.</action>
  <verify>npx vitest run resources/js/hooks/__tests__/use-appearance-consent.test.tsx resources/js/hooks/__tests__/use-theme-consent.test.tsx resources/js/components/ui/__tests__/sidebar-consent.test.tsx resources/js/components/__tests__/install-prompt-consent.test.tsx</verify>
  <done>The frontend regression layer proves that each app-owned optional persistence boundary is inactive without consent, is cleaned up when consent becomes disallowed, and still works when consent is accepted.</done>
</task>

</tasks>

<verification>
Before declaring plan complete:
- [ ] `php artisan test --compact tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`
- [ ] `npx vitest run resources/js/hooks/__tests__/use-appearance-consent.test.tsx resources/js/hooks/__tests__/use-theme-consent.test.tsx resources/js/components/ui/__tests__/sidebar-consent.test.tsx resources/js/components/__tests__/install-prompt-consent.test.tsx`
- [ ] `php artisan test --compact --filter=ConsentOptionalCookieEnforcementTest`
</verification>

<success_criteria>

- All tasks completed
- All verification checks pass
- No errors or warnings introduced
- Regression coverage locks the exact app-owned optional storage surface named in Phase 4 context and research
- Accepted-consent assertions remain present so denial behavior is proven as policy-driven, not as accidental permanent disablement
</success_criteria>

<output>
After completion, create `.planning/phases/04-verification-and-regression-coverage/02-optional-storage-regression-surface-SUMMARY.md`
</output>
