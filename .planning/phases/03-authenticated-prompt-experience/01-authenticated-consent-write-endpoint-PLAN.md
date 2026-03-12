---
phase: 03-authenticated-prompt-experience
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - app/Http/Controllers/ConsentController.php
  - app/Http/Requests/Consent/RecordConsentRequest.php
  - routes/web.php
  - resources/js/actions/App/Http/Controllers/ConsentController.ts
  - resources/js/actions/App/Http/Controllers/index.ts
  - tests/Feature/Auth/ConsentRecordEndpointTest.php
autonomous: true
requirements:
  - CONS-02
  - CONS-03
user_setup: []
must_haves:
  truths:
    - "Authenticated users can record an accepted consent decision through a server-owned endpoint."
    - "Authenticated users can record a declined consent decision through the same server-owned endpoint."
    - "After a successful consent write, the next authenticated Inertia response exposes the updated shared consent contract."
  artifacts:
    - path: app/Http/Controllers/ConsentController.php
      provides: Authenticated consent write controller
      min_lines: 15
    - path: app/Http/Requests/Consent/RecordConsentRequest.php
      provides: Consent write validation for accepted or declined states
      min_lines: 15
    - path: routes/web.php
      provides: Authenticated consent POST route
      contains: consent
    - path: resources/js/actions/App/Http/Controllers/ConsentController.ts
      provides: Wayfinder action for consent writes
      min_lines: 5
    - path: tests/Feature/Auth/ConsentRecordEndpointTest.php
      provides: Feature coverage for authenticated consent writes and redirects
      min_lines: 30
  key_links:
    - from: app/Http/Controllers/ConsentController.php
      to: app/Actions/Consent/RecordUserConsentAction.php
      via: controller delegation
      pattern: RecordUserConsentAction
    - from: routes/web.php
      to: app/Http/Controllers/ConsentController.php
      via: authenticated POST route
      pattern: ConsentController
    - from: resources/js/actions/App/Http/Controllers/ConsentController.ts
      to: routes/web.php
      via: Wayfinder generation
      pattern: consent
---

<objective>
Expose one authenticated Laravel write seam for `accepted` and `declined` consent decisions so the Phase 3 prompt can persist server-owned choices without reviving browser-owned consent state.

Purpose: complete the missing HTTP boundary between the shared authenticated consent contract and the existing `RecordUserConsentAction`.
Output: authenticated consent controller/request/route wiring, regenerated Wayfinder action, and targeted feature coverage.
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
@app/Actions/Consent/RecordUserConsentAction.php
@app/Http/Middleware/HandleInertiaRequests.php
@routes/web.php
</context>

<tasks>

<task type="auto">
  <name>Task 1: Add the authenticated consent write route and validation seam</name>
  <files>app/Http/Controllers/ConsentController.php, app/Http/Requests/Consent/RecordConsentRequest.php, routes/web.php</files>
  <action>Create a thin authenticated consent controller with a single `store` action, add a dedicated form request that validates `state` to `accepted|declined`, and register one authenticated POST route inside the existing auth + verified route group. Keep the controller limited to request validation, authenticated-user lookup, delegation to `RecordUserConsentAction`, and a generic redirect-back response so any authenticated page can round-trip through Inertia without page-specific logic. Do not move storage cleanup or prompt rendering into this backend plan.</action>
  <verify>php artisan test --compact tests/Feature/Auth/ConsentRecordEndpointTest.php</verify>
  <done>The codebase has exactly one authenticated POST endpoint for consent writes, guests cannot use it, invalid state is rejected, and accepted/declined states both persist through `RecordUserConsentAction`.</done>
</task>

<task type="auto">
  <name>Task 2: Regenerate Wayfinder and lock endpoint behavior with targeted feature coverage</name>
  <files>resources/js/actions/App/Http/Controllers/ConsentController.ts, resources/js/actions/App/Http/Controllers/index.ts, tests/Feature/Auth/ConsentRecordEndpointTest.php</files>
  <action>Run `php artisan wayfinder:generate --with-form` after the route exists so frontend code can import a typed consent action instead of hardcoding `/consent`. Add feature tests that cover accepted and declined writes, validation failure for invalid `state`, guest rejection by auth middleware, and the redirected follow-up response exposing the updated shared `consent` contract. Keep this verification focused on the write seam and shared-prop refresh, not full post-login end-to-end coverage reserved for Phase 4.</action>
  <verify>php artisan wayfinder:generate --with-form && php artisan test --compact --filter=ConsentRecordEndpointTest</verify>
  <done>Frontend imports can use the generated consent action, and targeted feature tests prove the backend write seam updates the server-owned consent contract on the next authenticated Inertia response.</done>
</task>

</tasks>

<verification>
Before declaring plan complete:
- [ ] `php artisan wayfinder:generate --with-form`
- [ ] `php artisan test --compact tests/Feature/Auth/ConsentRecordEndpointTest.php`
- [ ] `php artisan test --compact --filter=ConsentRecordEndpointTest`
- [ ] `composer lint`
</verification>

<success_criteria>

- All tasks completed
- All verification checks pass
- No errors or warnings introduced
- The authenticated consent route is server-owned and reusable from any authenticated page
- The generated Wayfinder action is available for the Phase 3 prompt
- Backend tests prove accepted and declined writes update the shared consent contract without reviving browser-owned consent state
</success_criteria>

<output>
After completion, create `.planning/phases/03-authenticated-prompt-experience/01-authenticated-consent-write-endpoint-SUMMARY.md`
</output>
