# Phase 1: Consent State And Delivery Contract - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

Establish one authoritative, versioned consent contract that Laravel can persist and Inertia can deliver to the authenticated app. This phase defines the consent source of truth, record shape, invalidation rules, and shared authenticated delivery contract. It does not implement the prompt UX, broad storage cleanup, or full verification coverage.

</domain>

<decisions>
## Implementation Decisions

### Consent record shape
- The consent record uses three explicit states: `undecided`, `accepted`, and `declined`.
- The record includes policy metadata, not just the decision itself.
- The record includes a single consent version plus decision/update timestamps.
- If the record is missing, invalid, or unreadable, the application treats the user as `undecided`.

### Authoritative persistence
- The authoritative consent record lives server-side on the authenticated `User`.
- Phase 1 should store the consent fields directly on `users`, not in a separate consent table.
- Browser storage is not a trusted fallback for consent state in this phase.
- Old frontend-only consent data in browser storage should be ignored rather than migrated into the new contract.

### Shared authenticated contract
- Laravel is the only trusted source of consent state for the authenticated app.
- The authenticated frontend receives consent through shared Inertia props on all authenticated pages.
- The shared contract includes at least consent state and consent version.
- The shared contract also includes a direct policy flag such as `allowOptionalStorage`, so frontend code does not have to reinterpret consent rules.
- When consent changes later, the app should round-trip through the server and refresh through the normal Inertia response cycle.

### Version and reset semantics
- v1 uses a single integer consent policy version.
- A consent version mismatch invalidates the previous decision and returns the user to `undecided`.
- In this increment, version mismatch is the only reset trigger.
- When consent is invalidated by version change, the user should simply see the normal prompt again without extra reset messaging.

### Claude's Discretion
- Exact naming of user fields and prop keys.
- Exact representation of timestamps and how they are serialized into Inertia props.
- Whether shared consent data is nested under an existing shared prop group or exposed as a dedicated top-level shared structure.

</decisions>

<specifics>
## Specific Ideas

- The app should stop relying on the existing browser-only consent helper as the source of truth.
- The server contract should be simple enough that later phases can enforce optional-storage policy without adding browser-side interpretation drift.
- The authenticated app should always be able to determine consent state from the server on the first post-login destination.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `resources/js/hooks/use-cookie-consent.tsx`: existing browser-only consent helper and version constant that should inform, but not define, the new contract.
- `app/Http/Middleware/HandleInertiaRequests.php`: existing shared-prop mechanism that can deliver consent state to all authenticated pages.
- `app/Http/Responses/LoginResponse.php`: existing post-login redirect seam relevant to how the authenticated app first receives consent state.
- `app/Http/Middleware/HandleAppearance.php`: existing server-side cookie reads for `appearance` and `theme`, showing where current browser preference behavior already affects SSR.

### Established Patterns
- Laravel is the backend authority for auth/session behavior, redirects, middleware, and shared Inertia props.
- The app already uses server-shared state to shape authenticated frontend behavior, so consent should follow that pattern instead of introducing a parallel client-only source.
- Existing frontend preference logic mixes localStorage and cookies, which increases the need for a single server-trusted consent contract before enforcement work begins.

### Integration Points
- `app/Models/User.php`: likely home for new consent fields in this phase.
- `app/Http/Middleware/HandleInertiaRequests.php`: likely delivery point for the shared authenticated consent contract.
- `resources/js/layouts/app/app-sidebar-layout.tsx` and the authenticated page flow: consumers of the shared contract in later phases.
- `resources/views/app.blade.php`, `resources/js/hooks/use-appearance.tsx`, and related preference hooks: downstream enforcement touchpoints that depend on the Phase 1 contract.

</code_context>

<deferred>
## Deferred Ideas

- User-facing consent management in settings is deferred to a later phase or milestone.
- Prompt UI, equal button prominence, and first-post-login display behavior are Phase 3 concerns.
- Cleanup and enforcement of optional cookies and browser storage are Phase 2 concerns.
- Automated verification and regression coverage are Phase 4 concerns.

</deferred>

---
*Phase: 01-consent-state-and-delivery-contract*
*Context gathered: 2026-03-12*
