# Convention Hosts

## What This Is

Convention Hosts is a Laravel 12, Inertia.js, and React 19 convention management system for organizing multi-day events with floors, sections, users, occupancy tracking, attendance reporting, and exports. It is used by authenticated convention staff with role-based access control, and it already includes guest onboarding, invitation flows, and a PWA-capable frontend.

This project initialization captures the next product increment: adding a cookie consent flow for authenticated users so consent is requested immediately after login when no prior choice exists, and non-essential cookies are not stored when consent is declined.

## Core Value

Convention staff can manage their events securely and reliably without the application storing non-essential cookies unless the user has explicitly accepted them.

## Requirements

### Validated

- ✓ Convention staff can create and manage conventions, floors, and sections — existing
- ✓ Role-based access control scopes what owners, convention users, floor users, and section users can do — existing
- ✓ Staff can track section occupancy and report attendance through authenticated workflows — existing
- ✓ The application supports invitation and guest verification flows for onboarding users into conventions — existing
- ✓ Convention data can be exported in multiple formats and the frontend already supports authenticated PWA usage — existing

### Active

- [ ] Authenticated users who have not already made a cookie decision are prompted for consent immediately after login
- [ ] The consent UI offers only two actions: accept all cookies or decline non-essential cookies
- [ ] If the user declines, the application stores only essential auth/session cookies required for sign-in and security
- [ ] Non-essential cookies are withheld until the user explicitly accepts them
- [ ] A user's consent decision persists so they are not repeatedly prompted on later logins unless the decision is reset

### Out of Scope

- Granular category management or a multi-toggle cookie preference center — excluded because this scope is intentionally limited to a simple accept-or-decline flow
- Anonymous pre-login consent prompts — excluded because the current requirement is specifically tied to authenticated login
- Keeping preference, theme, or install-dismissal cookies after decline — excluded because only auth/session cookies should remain when consent is declined

## Context

This is a brownfield Laravel monolith with Inertia-rendered React pages, generated Wayfinder routes, server-side authorization, and a PWA-capable frontend. Authentication uses Laravel Fortify with session-based auth, and the app already stores browser-facing state such as appearance and install-prompt dismissal through frontend logic and middleware.

The codebase already has secure headers, database-backed sessions, generated frontend routes, and a shared authenticated app layout used across the main product. Those existing patterns make the consent feature primarily a cross-cutting product change spanning authenticated entry flow, frontend UI placement, cookie/storage policy, and any client-side preference persistence that is currently treated as non-essential.

The immediate product goal surfaced from current work on the mobile install prompt and authenticated layout behavior: consent needs to be shown at the right time in the main app experience and must enforce a strict distinction between essential and non-essential browser storage.

## Constraints

- **Tech stack**: Must fit the existing Laravel 12 + Inertia.js + React 19 architecture — the app is already in production shape and should not introduce a parallel frontend or auth model
- **Authentication**: Essential cookies are limited to auth/session behavior — decline must not break login continuity or application security
- **Scope**: Consent flow must stay simple — only `Accept all` and `Decline` are in scope for this increment
- **Brownfield compatibility**: Existing appearance, PWA, and other client-side storage behavior must be audited against the new consent rules — current browser storage patterns may need adjustment
- **UX timing**: The prompt must appear immediately after login for undecided authenticated users — relying on hidden or collapsed navigation is not acceptable

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Show cookie consent immediately after login when no decision exists | The requirement is tied to authenticated entry, and the user should not miss the prompt | — Pending |
| Restrict the consent UI to `Accept all` and `Decline` | The desired flow is intentionally simple and should avoid a more complex preference center | — Pending |
| Treat only auth/session cookies as essential on decline | The user explicitly wants non-essential cookies blocked unless consent is accepted | — Pending |

---
*Last updated: 2026-03-12 after initialization*
