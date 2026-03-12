# Roadmap: Convention Hosts Cookie Consent Increment

**Created:** 2026-03-12
**Mode:** yolo
**Granularity:** standard
**Parallelization:** true
**Workflow flags:** research=true, plan_check=true, verifier=true, nyquist_validation=true

## Roadmap Principles

- This roadmap applies only to the cookie consent increment in the existing brownfield app.
- Existing convention, auth, reporting, export, and PWA capabilities are already validated and are not re-phased here.
- Every v1 requirement maps to exactly one phase.
- Phases are ordered by dependency so later work builds on earlier contracts and enforcement seams.

## Phase 1: Consent State And Delivery Contract

**Goal:** Establish one authoritative, versioned consent contract that Laravel can persist and Inertia can deliver to the authenticated app.

**Requirements covered:** `CONS-04`

**Success criteria:**
- The application has one server-authoritative consent state for authenticated users with explicit versioning or invalidation semantics.
- The authenticated app can determine whether a user is `undecided`, `accepted`, or `declined` without relying on browser-only state.
- Consent state is available to the authenticated frontend through a shared contract suitable for all post-login destinations.

**Initial status:** Completed

## Phase 2: Storage Enforcement And Safe Defaults

**Goal:** Enforce essential-only behavior after decline and route optional browser persistence through one centralized consent policy.

**Requirements covered:** `STOR-01`, `STOR-02`, `STOR-03`, `STOR-04`, `APPX-03`

**Success criteria:**
- Declined consent leaves only essential auth/session cookies active for sign-in and security-sensitive flows.
- Non-essential cookies or browser storage are not created before consent is accepted.
- Existing optional cookies and browser storage created before decline are cleared, ignored, or otherwise made inactive after decline.
- Authenticated preference writes flow through one consent-aware policy instead of direct ad hoc storage writes.
- The authenticated app remains usable with safe defaults when optional persistence is unavailable.

**Initial status:** Pending

## Phase 3: Authenticated Prompt Experience

**Goal:** Integrate the consent prompt into the shared authenticated shell so undecided users must choose immediately after login.

**Requirements covered:** `CONS-01`, `CONS-02`, `CONS-03`, `APPX-01`, `APPX-02`

**Success criteria:**
- An authenticated user with no consent decision sees the prompt on the first post-login destination in the shared app experience.
- The prompt exposes only `Accept all` and `Decline`, with equal prominence and no granular preference center.
- Choosing `Accept all` records acceptance and enables optional persistence behavior governed by the consent contract.
- Choosing `Decline` records refusal and keeps the app operating under the essential-only storage policy.

**Initial status:** Pending

## Phase 4: Verification And Regression Coverage

**Goal:** Prove the consent flow works across login, storage enforcement, and essential session continuity in this brownfield app.

**Requirements covered:** `VERI-01`, `VERI-02`, `VERI-03`

**Success criteria:**
- Automated tests verify that undecided authenticated users are prompted immediately after login.
- Automated tests verify that declined consent prevents non-essential cookies or browser storage from being treated as active state.
- Automated tests verify that essential auth/session behavior continues to work when consent is declined.
- Regression coverage exercises the critical integration boundaries for this increment rather than only isolated unit behavior.

**Initial status:** Pending

## Traceability Summary

| Requirement | Phase |
|-------------|-------|
| `CONS-01` | Phase 3: Authenticated Prompt Experience |
| `CONS-02` | Phase 3: Authenticated Prompt Experience |
| `CONS-03` | Phase 3: Authenticated Prompt Experience |
| `CONS-04` | Phase 1: Consent State And Delivery Contract |
| `STOR-01` | Phase 2: Storage Enforcement And Safe Defaults |
| `STOR-02` | Phase 2: Storage Enforcement And Safe Defaults |
| `STOR-03` | Phase 2: Storage Enforcement And Safe Defaults |
| `STOR-04` | Phase 2: Storage Enforcement And Safe Defaults |
| `APPX-01` | Phase 3: Authenticated Prompt Experience |
| `APPX-02` | Phase 3: Authenticated Prompt Experience |
| `APPX-03` | Phase 2: Storage Enforcement And Safe Defaults |
| `VERI-01` | Phase 4: Verification And Regression Coverage |
| `VERI-02` | Phase 4: Verification And Regression Coverage |
| `VERI-03` | Phase 4: Verification And Regression Coverage |

## Coverage Validation

- v1 requirements total: 14
- Mapped to phases: 14
- Unmapped: 0
- Duplicate mappings: 0
- Coverage status: 100%

## Current Execution Order

1. Phase 1: Consent State And Delivery Contract
2. Phase 2: Storage Enforcement And Safe Defaults
3. Phase 3: Authenticated Prompt Experience
4. Phase 4: Verification And Regression Coverage

---
*Last updated: 2026-03-12 during initial roadmap creation*
