# Phase 2: Storage Enforcement And Safe Defaults - Context

**Gathered:** 2026-03-12
**Status:** Ready for planning

<domain>
## Phase Boundary

Enforce essential-only behavior after decline and route optional browser persistence through one centralized consent policy. This phase decides how the authenticated app stops reading and writing optional cookies and browser storage, what safe defaults it uses when persistence is blocked, and how known optional state is cleared when a user declines consent. It does not introduce the consent prompt UX itself or expand into broader settings/consent-management features.

</domain>

<decisions>
## Implementation Decisions

### Theme and appearance behavior
- If consent is declined, theme and appearance use safe defaults instead of persisted preferences.
- Theme and appearance should not be persisted after decline.
- The fallback appearance should use the system light/dark preference.
- The fallback color theme should use the app default theme.
- If a user declines after previously allowing consent, existing theme and appearance cookies/localStorage should be cleared immediately.

### Sidebar and shell persistence
- If consent is declined, sidebar state falls back to the app’s built-in default on each visit.
- Sidebar open/closed changes should not be persisted after decline.
- The server should stop trusting `sidebar_state` when consent is declined.
- Existing `sidebar_state` cookies should be actively cleared, not just ignored.

### Install prompt persistence
- Install-prompt dismissal should not be persisted when consent is declined.
- Without persistence, the install prompt may show again on later eligible visits.
- If a user previously allowed consent and later declines, the stored install-prompt dismissal key should be cleared immediately.

### Decline cleanup behavior
- Known optional cookies and browser storage should be cleared immediately when a user declines.
- Cleanup scope in this phase should target the explicit known optional keys already identified in the app, not a broad dynamic sweep of unknown keys.
- Cleanup should be driven by one explicit allowlist/cleanup list so behavior is deterministic and testable.

### Claude's Discretion
- Exact organization of the centralized consent-aware storage helper(s).
- Exact names and placement of the explicit cleanup list / optional-key registry.
- Whether default shell state is represented as a server fallback, client fallback, or both, as long as the agreed behavior stays intact.

</decisions>

<specifics>
## Specific Ideas

- Decline should aggressively remove known optional state rather than merely stopping future writes.
- The brownfield app should keep working with stable defaults even when no optional persistence is available.
- Preference and shell behavior should become consent-aware through one policy seam instead of repeated ad hoc `canSetCookies()` checks.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `app/Support/Consent/UserConsentResolver.php`: already exposes `allowOptionalStorage`, which should become the enforcement signal for optional persistence.
- `app/Http/Middleware/HandleInertiaRequests.php`: already shares the top-level `consent` prop and currently still trusts `sidebar_state`.
- `resources/js/hooks/use-cookie-consent.tsx`: current browser-only helper still exists and is a likely source of legacy behavior that Phase 2 must stop treating as authoritative.

### Established Patterns
- Theme and appearance currently write to both localStorage and cookies in `resources/js/hooks/use-appearance.tsx` and `resources/js/hooks/use-theme.tsx`.
- Blade/bootstrap currently reads persisted theme data in `resources/views/app.blade.php`, including a localStorage fallback for theme before the server sees cookies.
- Sidebar persistence currently writes `sidebar_state` directly from `resources/js/components/ui/sidebar.tsx`, and the backend currently reads it in `app/Http/Middleware/HandleInertiaRequests.php`.
- Install prompt dismissal currently persists through `localStorage` in `resources/js/components/install-prompt.tsx`.

### Integration Points
- `resources/js/hooks/use-appearance.tsx`
- `resources/js/hooks/use-theme.tsx`
- `resources/views/app.blade.php`
- `resources/js/components/ui/sidebar.tsx`
- `app/Http/Middleware/HandleAppearance.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/components/install-prompt.tsx`
- Any tests covering hook behavior, authenticated props, or cookie-consent hook interactions

</code_context>

<deferred>
## Deferred Ideas

- Prompt UI, accept/decline button behavior, and first post-login prompt visibility remain Phase 3 concerns.
- Broader user-facing consent management settings remain out of scope for this phase.
- Broad dynamic cleanup of unknown browser keys is deferred; this phase should focus on known optional app-owned keys.
- End-to-end regression verification across the whole consent flow remains Phase 4 work.

</deferred>

---
*Phase: 02-storage-enforcement-and-safe-defaults*
*Context gathered: 2026-03-12*
