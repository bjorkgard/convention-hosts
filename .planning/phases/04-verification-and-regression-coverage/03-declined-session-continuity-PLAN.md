---
phase: 04-verification-and-regression-coverage
plan: 03
type: execute
wave: 3
depends_on:
  - "01"
  - "02"
files_modified:
  - tests/Feature/Auth/ConsentSessionContinuityTest.php
autonomous: true
requirements:
  - VERI-03
user_setup: []
must_haves:
  truths:
    - "Declining consent does not prevent successful login or the authenticated redirect flow from establishing the essential session."
    - "A declined user can continue to navigate to later authenticated pages in the same session."
    - "The declined login response still carries essential session and CSRF cookies needed for later authenticated requests."
    - "A later authenticated POST request still succeeds under the same declined-consent session."
  artifacts:
    - path: tests/Feature/Auth/ConsentSessionContinuityTest.php
      provides: Dedicated feature proof for essential authenticated continuity after decline
      min_lines: 40
  key_links:
    - from: tests/Feature/Auth/ConsentSessionContinuityTest.php
      to: routes/web.php
      via: follow-up authenticated POST request
      pattern: consent
    - from: tests/Feature/Auth/ConsentSessionContinuityTest.php
      to: app/Http/Responses/LoginResponse.php
      via: declined login redirect and authenticated session assertions
      pattern: login.store
---

<objective>
Lock `VERI-03` with one dedicated backend feature file that proves declining consent preserves the essential authenticated session, navigation continuity, and later request capability the app still needs to function.

Purpose: separate continuity proof from optional-storage enforcement so this requirement remains visible and requirement-shaped instead of being buried inside a broader storage test.
Output: one new feature test file covering declined login, essential cookies, later authenticated navigation, and a later authenticated POST under the same session.
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
@tests/Feature/Auth/ConsentRecordEndpointTest.php
@app/Http/Responses/LoginResponse.php
@routes/web.php
</context>

<tasks>

<task type="auto">
  <name>Task 1: Add a dedicated declined-login continuity feature suite</name>
  <files>tests/Feature/Auth/ConsentSessionContinuityTest.php</files>
  <action>Create a new Phase 4 feature file instead of adding more assertions to the optional-cookie enforcement suite. Cover declined-consent login through the real `login.store` route, assert the user remains authenticated after the redirect, assert the login response includes the essential session cookie and `XSRF-TOKEN`, and assert a later authenticated GET still succeeds under the same session. Keep the file narrowly focused on continuity rather than repeating storage-policy assertions already proven elsewhere.</action>
  <verify>php artisan test --compact tests/Feature/Auth/ConsentSessionContinuityTest.php</verify>
  <done>The dedicated continuity suite proves a declined user can sign in and continue authenticated navigation with the essential cookies still present.</done>
</task>

<task type="auto">
  <name>Task 2: Prove later authenticated POST behavior still works after decline</name>
  <files>tests/Feature/Auth/ConsentSessionContinuityTest.php</files>
  <action>Within the same continuity file, add a follow-up authenticated POST scenario under the same declined session, preferably using the existing consent write route to switch from declined to accepted because that keeps the proof within the increment's boundary. Assert the request succeeds and the user remains authenticated after the round-trip. Do not turn this into a separate CSRF middleware audit; the goal is to prove consent decline does not break later authenticated interactions.</action>
  <verify>php artisan test --compact --filter=ConsentSessionContinuityTest</verify>
  <done>The continuity suite proves later authenticated POST requests still work after a user declines consent, confirming essential session and XSRF plumbing remain intact.</done>
</task>

</tasks>

<verification>
Before declaring plan complete:
- [ ] `php artisan test --compact tests/Feature/Auth/ConsentSessionContinuityTest.php`
- [ ] `php artisan test --compact --filter=ConsentSessionContinuityTest`
- [ ] `php artisan test --compact --filter=Consent`
</verification>

<success_criteria>

- All tasks completed
- All verification checks pass
- No errors or warnings introduced
- Declined consent is proven compatible with login, later authenticated navigation, and later authenticated POST requests
- Essential session and XSRF cookies remain part of the declined flow while optional storage stays outside this plan's scope
</success_criteria>

<output>
After completion, create `.planning/phases/04-verification-and-regression-coverage/03-declined-session-continuity-SUMMARY.md`
</output>
