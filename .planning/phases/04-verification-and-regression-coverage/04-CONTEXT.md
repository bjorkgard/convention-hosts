# Phase 4: Verification And Regression Coverage - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

Prove the authenticated cookie consent flow works across login, storage enforcement, and essential session continuity in this brownfield app. This phase decides what regression coverage must exist to verify the first authenticated prompt experience, the decline/undecided enforcement surface, and the continuity of essential authenticated behavior. It does not redesign the consent UX, change enforcement rules, or add new consent capabilities.

</domain>

<decisions>
## Implementation Decisions

### Post-login prompt verification style
- Phase 4 should verify the prompt on the real first authenticated destination, not only through isolated component tests.
- The core proof should use backend feature or integration-style coverage that follows the real Fortify redirect flow.
- Verification should prove prompt visibility for `undecided` users after standard password login.
- Verification should also prove prompt visibility after two-factor completion.
- Coverage should include both first-destination shapes already used by the app: one convention and multiple conventions.

### Decline enforcement regression surface
- Phase 4 should verify the known app-owned optional state at the integration boundary rather than reopening a generic browser-storage sweep.
- The regression surface should include `appearance`, `theme`, `sidebar_state`, and install-prompt dismissal.
- Tests should prove that declined or undecided consent does not allow those values to become active state.
- The same regression surface should include at least one accepted-consent happy-path assertion so the regression proof shows the enforcement is conditional rather than globally disabled.

### Essential authenticated continuity
- Phase 4 should explicitly prove that declining consent does not break essential authenticated continuity.
- Verification should cover successful login still establishing the authenticated session.
- Verification should cover subsequent authenticated navigation still working after decline.
- Verification should cover the session and CSRF continuity needed for later authenticated requests.
- This phase should stay focused on consent-related continuity and should not broaden into unrelated authorization-matrix testing.

### Backend and frontend test balance
- Phase 4 should lean primarily on backend feature or integration coverage because the remaining risk is cross-request behavior through Fortify redirects, Inertia responses, cookies, and session continuity.
- Frontend tests in this phase should stay targeted and only support gaps the backend assertions cannot express cleanly.
- The core proof should come from authenticated request/response flows rather than a broad frontend test rewrite.

### Claude's Discretion
- Exact test file split between new feature tests and targeted supporting frontend tests.
- Whether Phase 4 adds to existing consent test files or introduces a new dedicated end-to-end regression test file per scenario cluster.
- Exact use of Pest datasets, helpers, or shared assertions as long as the locked regression surface is fully covered.

</decisions>

<specifics>
## Specific Ideas

- The key remaining proof is not “does the component render” but “does the full authenticated flow behave correctly across redirects, shared props, and subsequent requests.”
- Phase 3 already proved the shell prompt component and server write seam in isolation; Phase 4 should prove those pieces hold together in real authenticated navigation.
- The accepted-consent path should remain present in regression tests wherever the same surface is asserted, so decline behavior is proven as conditional enforcement rather than accidental disablement.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `tests/Feature/Auth/ConsentLoginFlowTest.php`: already proves the shared `consent` contract reaches the first authenticated Inertia response for password login and two-factor completion.
- `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`: already proves server-side optional cookie trust and forgetting behavior for accepted, declined, and undecided consent.
- `tests/Feature/Auth/ConsentRecordEndpointTest.php`: already proves server-owned accept/decline writes and refreshed shared consent props.
- `resources/js/components/__tests__/authenticated-consent-prompt.test.tsx`: already covers the prompt component in isolation and can remain a supporting frontend check.

### Established Patterns
- Fortify login and two-factor flows already determine the first authenticated destination through existing response classes, so regression tests should follow those real redirects instead of mocking alternate entry paths.
- The shared Inertia `consent` prop is already the authenticated source of truth; Phase 4 should verify that prompt visibility and storage enforcement hold through real authenticated responses.
- Phase 2 and Phase 3 already split enforcement and prompt behavior; Phase 4’s job is to prove they integrate correctly together.

### Integration Points
- `app/Http/Responses/LoginResponse.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Middleware/HandleAppearance.php`
- `app/Support/Consent/OptionalStorageRegistry.php`
- `resources/js/layouts/app/app-sidebar-layout.tsx`
- `resources/js/components/authenticated-consent-prompt.tsx`
- Existing consent feature tests under `tests/Feature/Auth/`

</code_context>

<deferred>
## Deferred Ideas

- Broad browser-driven manual QA beyond targeted regression proof is outside this phase.
- Anonymous pre-login consent verification remains out of scope because anonymous consent is not part of this increment.
- User-managed consent changes from settings remain future work.
- Any broader frontend testing overhaul unrelated to consent regression is deferred.

</deferred>

---
*Phase: 04-verification-and-regression-coverage*
*Context gathered: 2026-03-12*
