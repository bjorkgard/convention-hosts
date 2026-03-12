# Phase 3: Authenticated Prompt Experience - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

Integrate the cookie consent prompt into the shared authenticated shell so undecided users must choose immediately after login. This phase decides where the prompt lives in the authenticated app, how it behaves while consent is undecided, how accept and decline are recorded through the server-owned consent path, and how the legacy client-only banner is replaced. It does not reopen storage-policy enforcement, add granular preferences, or broaden into full regression coverage.

</domain>

<decisions>
## Implementation Decisions

### Prompt surface and placement
- The consent prompt is mounted in the shared authenticated app shell, not inside a page-specific screen.
- It must be visible on the first authenticated destination after login without requiring the user to open the sidebar.
- It should sit at the bottom of the main authenticated page area, consistent with the current shell placement used for other bottom-mounted prompts.
- The prompt should be an in-app banner or sheet in the authenticated shell, not a full-page redirect and not a separate consent screen.

### Prompt behavior while undecided
- The prompt renders whenever the shared authenticated consent state is `undecided`.
- The app behind the prompt remains visible and usable in v1; this is not a blocking modal-overlay phase.
- The prompt remains persistently visible until the user records a decision.
- Reloading or navigating while still `undecided` should continue showing the prompt on authenticated pages.
- Once the consent state becomes `accepted` or `declined`, the prompt disappears through the normal Inertia refresh cycle.

### Accept and decline interaction flow
- The prompt exposes only two actions: `Accept all` and `Decline`.
- The two actions should have equal visual prominence in v1.
- Both actions persist immediately through the server-owned consent write path.
- After either action, the authenticated app should refresh through the normal Inertia cycle and stop rendering the prompt.
- `Accept all` enables optional storage under the existing server-shared consent contract.
- `Decline` keeps the app under the existing essential-only storage enforcement from Phase 2.

### Legacy banner replacement strategy
- The existing localStorage-backed banner should be replaced rather than mounted alongside the new authenticated prompt.
- Phase 3 should stop treating `resources/js/hooks/use-cookie-consent.tsx` as the active consent write path for authenticated users.
- The authenticated prompt should consume the shared Inertia consent contract and server-backed write seam instead of writing consent records into browser storage.
- Legacy banner tests and compatibility code can be removed or rewritten if they only validate the superseded localStorage flow.

### Claude's Discretion
- Exact banner or sheet styling within the existing authenticated layout language.
- Exact component/file organization for the new authenticated prompt surface.
- Whether the prompt action round-trip uses an explicit controller endpoint, form action, or another standard Laravel + Inertia write seam, as long as it remains server-owned and immediate.

</decisions>

<specifics>
## Specific Ideas

- The prompt should feel like part of the authenticated app shell rather than a detached legal screen.
- The earlier install-prompt placement concern established that bottom-of-main-content placement is the right visibility pattern for mobile authenticated users.
- Equal prominence between `Accept all` and `Decline` is a deliberate product decision for v1.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `resources/js/layouts/app/app-sidebar-layout.tsx`: the shared authenticated shell and the most natural mount point for the Phase 3 prompt.
- `app/Actions/Consent/RecordUserConsentAction.php`: existing server-owned write seam for recording `accepted` or `declined`.
- `app/Http/Middleware/HandleInertiaRequests.php`: already shares the top-level authenticated `consent` contract used to decide prompt visibility.
- `resources/js/hooks/use-consent.ts`: existing typed React seam for reading the trusted shared consent contract.

### Established Patterns
- Authenticated entry still follows the normal Laravel Fortify redirect in `app/Http/Responses/LoginResponse.php`, so the prompt must appear on the first authenticated destination instead of introducing a custom post-login consent page.
- Phase 1 established Laravel as the only trusted consent source for authenticated pages.
- Phase 2 already enforces essential-only storage behavior when consent is not accepted, so the prompt can rely on those enforcement seams rather than adding duplicate browser cleanup logic.
- The existing `resources/js/components/cookie-consent-banner.tsx` and `resources/js/hooks/use-cookie-consent.tsx` are legacy localStorage-driven behavior and should not remain the authenticated source of truth.

### Integration Points
- `resources/js/layouts/app/app-sidebar-layout.tsx`
- `resources/js/components/cookie-consent-banner.tsx` or its replacement component
- `resources/js/hooks/use-consent.ts`
- `app/Actions/Consent/RecordUserConsentAction.php`
- The eventual authenticated consent POST endpoint/controller or form action
- Existing authenticated login flow and shared Inertia refresh behavior

</code_context>

<deferred>
## Deferred Ideas

- User-managed consent changes from a later settings page remain a future phase.
- Anonymous pre-login consent is still out of scope for this increment.
- End-to-end verification of immediate post-login rendering and consent flows remains Phase 4 work.
- Any broader consent copy/legal-content iteration beyond the two-action v1 prompt is deferred.

</deferred>

---
*Phase: 03-authenticated-prompt-experience*
*Context gathered: 2026-03-12*
