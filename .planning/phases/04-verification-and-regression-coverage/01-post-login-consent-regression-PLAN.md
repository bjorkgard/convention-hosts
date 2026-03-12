---
phase: 04-verification-and-regression-coverage
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - tests/Feature/Auth/ConsentLoginFlowTest.php
  - resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx
autonomous: true
requirements:
  - VERI-01
user_setup: []
must_haves:
  truths:
    - "Real Fortify password login delivers an `undecided` consent contract on the first authenticated Inertia response for both supported redirect shapes."
    - "Real Fortify two-factor completion delivers the same `undecided` contract on the first authenticated Inertia response for both supported redirect shapes."
    - "The shared authenticated shell mounts the consent prompt when `consent.state` is `undecided` and does not mount it once consent is accepted or declined."
  artifacts:
    - path: tests/Feature/Auth/ConsentLoginFlowTest.php
      provides: Regression coverage for real login and two-factor first-destination consent delivery
      min_lines: 40
    - path: resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx
      provides: Shell-level prompt visibility regression test at the real authenticated mount point
      min_lines: 30
  key_links:
    - from: tests/Feature/Auth/ConsentLoginFlowTest.php
      to: app/Http/Responses/LoginResponse.php
      via: password login redirect assertions
      pattern: conventions.show
    - from: tests/Feature/Auth/ConsentLoginFlowTest.php
      to: app/Http/Responses/TwoFactorLoginResponse.php
      via: two-factor redirect assertions
      pattern: two-factor.login.store
    - from: resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx
      to: resources/js/layouts/app/app-sidebar-layout.tsx
      via: shared authenticated shell rendering
      pattern: AuthenticatedConsentPrompt
---

<objective>
Lock `VERI-01` with regression coverage that follows the app's real Fortify login paths and proves the shared authenticated shell shows the consent prompt only when the first authenticated payload is still `undecided`.

Purpose: prove the Phase 3 prompt experience holds across the actual password and two-factor redirect flow instead of only isolated component tests.
Output: expanded login feature coverage and one new shell-level Vitest file for prompt visibility at the real mount point.
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
@tests/Feature/Auth/ConsentLoginFlowTest.php
@app/Http/Responses/LoginResponse.php
@app/Http/Responses/TwoFactorLoginResponse.php
@resources/js/layouts/app/app-sidebar-layout.tsx
@resources/js/components/authenticated-consent-prompt.tsx
</context>

<tasks>

<task type="auto">
  <name>Task 1: Extend the real login-flow feature proof to both first-destination shapes</name>
  <files>tests/Feature/Auth/ConsentLoginFlowTest.php</files>
  <action>Extend the existing consent login regression file instead of creating a parallel suite. Add password-login coverage for both production redirect shapes the response classes already use: exactly one convention landing on `conventions/show` and multi-convention or no-convention landing on `conventions/index`. Add the same matrix for two-factor completion. In every scenario, follow the real Fortify redirect, assert the user is authenticated, assert the final Inertia component matches the real destination, and assert the shared `consent` payload remains `undecided` with `allowOptionalStorage` false. Do not replace this with direct authenticated GET requests because that would skip the boundary this requirement is meant to prove.</action>
  <verify>php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php</verify>
  <done>`ConsentLoginFlowTest` proves real password and two-factor login flows land on the first authenticated Inertia response with the expected `undecided` consent contract for both supported destination shapes.</done>
</task>

<task type="auto">
  <name>Task 2: Add shell-level prompt visibility coverage at the actual authenticated mount point</name>
  <files>resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx</files>
  <action>Create one focused Vitest file for `AppSidebarLayout` that renders the real authenticated shell with controlled page props. Prove that `AuthenticatedConsentPrompt` is mounted when `consent.state` is `undecided`, and that it is absent when consent is accepted or declined. Keep this test layout-scoped rather than re-testing the prompt component's submission behavior, and do not broaden it into unrelated sidebar or install-prompt assertions already covered elsewhere.</action>
  <verify>npx vitest run resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx</verify>
  <done>The shell-level test closes the integration gap between the backend's shared `consent` payload and the actual authenticated layout that decides whether the prompt becomes visible.</done>
</task>

</tasks>

<verification>
Before declaring plan complete:
- [ ] `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php`
- [ ] `npx vitest run resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx`
- [ ] `php artisan test --compact --filter=ConsentLoginFlowTest`
</verification>

<success_criteria>

- All tasks completed
- All verification checks pass
- No errors or warnings introduced
- Regression coverage proves undecided users are prompted on the first authenticated destination reached through the real password and two-factor flows
- The prompt-visibility proof is anchored to the shared authenticated shell instead of a synthetic page or direct component-only test
</success_criteria>

<output>
After completion, create `.planning/phases/04-verification-and-regression-coverage/01-post-login-consent-regression-SUMMARY.md`
</output>
