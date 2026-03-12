# Project Research Summary

**Project:** Convention Hosts
**Domain:** authenticated cookie consent increment in an existing Laravel + Inertia + React application
**Researched:** 2026-03-12
**Confidence:** HIGH

## Executive Summary

Convention Hosts is an existing authenticated convention-management product, not a greenfield marketing site. That matters because this increment is primarily a cross-cutting storage-policy change inside the authenticated app shell: users must be prompted immediately after login when no consent decision exists, declining must still preserve essential session/auth behavior, and all non-essential persistence must be blocked or cleared. The research consistently points to a first-party implementation using Laravel as the authoritative consent source, Inertia shared props as the delivery contract, and the shared authenticated React layout as the mount point for the prompt.

The recommended approach is to avoid a third-party CMP and instead add a server-visible, versioned consent record plus a single consent policy that governs both PHP-side cookie behavior and client-side storage writes. The highest-risk area is not the banner itself; it is the existing brownfield preference storage spread across theme, appearance, sidebar, install-prompt dismissal, and Blade/bootstrap behavior. Roadmap phases should therefore start with consent state and enforcement seams before UI polish, so the project does not ship a cosmetically correct banner with ineffective decline behavior.

## Key Findings

### Recommended Stack

This increment fits the current stack and does not justify new platform dependencies. Laravel 12 should own persistence and enforcement, Inertia should expose a shared consent contract to every authenticated page, and React 19 should render the prompt in the authenticated shell. Optional browser persistence can remain first-party, but only after it is routed through a centralized consent gate.

**Core technologies:**
- Laravel 12: authoritative consent persistence, middleware enforcement, and optional-cookie cleanup.
- Inertia.js: shared authenticated props so every post-login destination receives the same consent state.
- React 19: shell-level consent UI and client-side gating for optional storage writers.
- Browser storage APIs: allowed only for non-essential preferences after acceptance, never as the sole source of truth.

### Expected Features

Research across the feature brief is aligned: the MVP is a binary authenticated consent flow, not a generalized preference center. The launch bar is immediate post-login prompting for undecided users, durable decision persistence, meaningful decline behavior, and graceful fallback when optional preference storage is unavailable.

**Must have (table stakes):**
- Immediate post-login consent prompt for authenticated users with no prior decision.
- Binary `Accept all` and `Decline` actions with equal prominence.
- Persistent consent state so users are not repeatedly prompted.
- Essential-only operation after decline, with non-essential cookies and similar storage blocked.
- Safe defaults for theme, appearance, sidebar, and install-prompt behavior when persistence is disallowed.

**Should have (competitive):**
- Cross-device persistence if consent is stored against the authenticated user.
- Lightweight visibility or reset path for supportability after the core flow is stable.

**Defer (v2+):**
- Granular cookie categories and preference-center UI.
- Anonymous pre-login consent handling.
- Region-specific or policy-heavy CMP behavior.

### Architecture Approach

The architecture research is clear on the main seam: consent should be resolved on the server, shared through `HandleInertiaRequests`, and consumed once in the authenticated layout. From there, all preference hooks and middleware should depend on a single storage policy rather than writing cookies or `localStorage` directly.

**Major components:**
1. Consent state model/service: resolves `undecided`, `accepted`, or `declined`, tracks version/timestamp, and defines whether optional storage is allowed.
2. Laravel middleware and shared props: publish consent state to the frontend and prevent optional cookies from being treated as authoritative after decline.
3. Authenticated app shell and storage adapters: show the prompt once in the shared layout and route theme/appearance/sidebar/install-prompt persistence through the same policy.

### Critical Pitfalls

The main delivery risks are architectural, not cosmetic.

1. **Client-only consent state**: if consent remains only in `localStorage`, Laravel cannot enforce or reason about decline; avoid this with a server-visible, versioned contract.
2. **Optional storage before consent**: theme, appearance, or install-prompt writes can happen before the banner appears; avoid this with a full storage audit and centralized gating.
3. **Decline breaking auth/session continuity**: broad cookie blocking can break Fortify/session/CSRF behavior; avoid this with an explicit essential-cookie allowlist.
4. **Old optional state surviving decline**: preventing future writes is not enough; avoid this with deterministic cleanup of existing optional cookies and storage keys.
5. **Prompt timing that is technically present but product-wrong**: page-level or delayed mounting can miss the first authenticated entry; avoid this by mounting in the shared authenticated layout and testing first-load behavior.

## Implications for Roadmap

Based on the research, the increment should be planned as four tightly scoped phases.

### Phase 1: Consent State And Policy Contract
**Rationale:** Everything else depends on one authoritative answer about whether the user is undecided, accepted, or declined.
**Delivers:** Server-backed consent model, consent versioning, essential-vs-optional policy, and shared Inertia consent props.
**Addresses:** Persistent consent state, immediate post-login prompt prerequisites, essential-only behavior after decline.
**Avoids:** Client-only consent drift and accidental auth/session breakage.

### Phase 2: Storage Enforcement And Cleanup
**Rationale:** The existing app already writes optional state in multiple places, so enforcement has to land before the UI can be trusted.
**Delivers:** Centralized browser-storage gate, refactors for theme/appearance/sidebar/install-prompt persistence, cleanup of previously written optional cookies and `localStorage` keys, and consent-aware Blade/bootstrap behavior.
**Uses:** Laravel middleware, shared consent props, React hooks, and browser storage APIs.
**Implements:** Policy-wrapped preference storage and server-side optional-cookie suppression.

### Phase 3: Authenticated Prompt Integration
**Rationale:** Once the policy contract and storage enforcement are in place, the banner can become a thin UI over a reliable backend rule set.
**Delivers:** Prompt mounted in the authenticated layout, equal-weight `Accept all` and `Decline` actions, immediate post-login display for undecided users, and predictable interaction priority against install/update prompts.
**Implements:** Shell-level prompting in the shared authenticated app layout.

### Phase 4: Verification And Operational Hardening
**Rationale:** This increment crosses login, middleware, SSR, React hydration, and browser storage, so verification needs its own explicit phase.
**Delivers:** Feature tests for login and consent persistence, frontend tests for prompt rendering and storage gating, regression coverage for hard refresh and decline cleanup, and validation that optional state is ignored after refusal.
**Addresses:** SSR/client parity, revocation confidence, and brownfield regression risk.

### Phase Ordering Rationale

- Phase 1 comes first because every other concern depends on a server-authoritative consent contract.
- Phase 2 follows because decline semantics are the real complexity in this app; the banner is low risk compared with existing storage behavior.
- Phase 3 is intentionally after enforcement so the product does not ship a banner whose decline path is only partially effective.
- Phase 4 is separated because the highest-probability failures happen at integration boundaries: login redirects, hard refresh, SSR boot, and pre-existing browser state.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 1:** Decide whether consent persistence belongs on the `users` table or another server-visible store; both are viable, but the roadmap should pick one deliberately.
- **Phase 2:** Audit all current optional storage paths, especially any remaining Blade/bootstrap reads and middleware reads beyond the known theme/appearance/sidebar/install-prompt paths.
- **Phase 4:** Define the exact browser-level verification matrix for fresh users, previously accepted users, previously declined users, and users carrying old optional state.

Phases with standard patterns (skip research-phase):
- **Phase 3:** The UI itself is straightforward once the server contract and storage policy already exist.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Strong fit with the current Laravel + Inertia + React architecture; no new platform dependency is needed. |
| Features | HIGH | Scope is narrow and well-defined: authenticated, binary consent, no preference center. |
| Architecture | HIGH | The correct seams are well-supported by the existing app structure and research findings. |
| Pitfalls | HIGH | Risks are concrete, brownfield-specific, and repeatedly reinforced by the codebase context. |

**Overall confidence:** HIGH

### Gaps to Address

- Persistence choice: planning should explicitly choose the authoritative storage location for consent and define how version invalidation works.
- Storage inventory completeness: planning should confirm no other optional cookies or browser keys exist beyond the known preference and install-prompt paths.
- Consent reset visibility: the MVP can launch without a user-facing settings surface, but the roadmap should decide whether that lands in this increment or a follow-up.

## Sources

### Primary (HIGH confidence)
- `.planning/PROJECT.md` — increment goals, scope boundaries, and core constraints.
- `.planning/research/STACK.md` — recommended implementation stack and storage-policy direction.
- `.planning/research/FEATURES.md` — MVP feature priorities and launch-vs-defer boundaries.
- `.planning/research/ARCHITECTURE.md` — preferred integration seams, data flow, and build order.
- `.planning/research/PITFALLS.md` — brownfield failure modes and verification concerns.

### Secondary (MEDIUM confidence)
- `/Users/nathanael/.codex/get-shit-done/templates/research-project/SUMMARY.md` — required summary shape for roadmap input.

---
*Research completed: 2026-03-12*
*Ready for roadmap: yes*
