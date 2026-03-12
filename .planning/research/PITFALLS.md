# Pitfalls Research

**Domain:** cookie consent for an authenticated Laravel + Inertia web app
**Researched:** 2026-03-12
**Confidence:** HIGH

## Critical Pitfalls

### Pitfall 1: Consent is recorded only in client storage

**What goes wrong:**
The app remembers the consent choice in `localStorage`, but the server cannot see that decision on the next request. SSR, middleware, and PHP-side cookie logic then continue behaving as if no choice exists, or they fall back to old optional cookies.

**Why it happens:**
Teams treat the consent banner as a frontend-only feature and forget that Laravel middleware, Blade, SSR, and response cookies need the same source of truth.

**How to avoid:**
Persist the decision in a server-visible form as well as the client UI state. Define one canonical consent state, version it, and make both PHP and React read from it consistently. If decline is allowed, add a cleanup path for optional cookies already issued before the decision.

**Warning signs:**
- The decision exists only in `localStorage`
- Middleware still reads optional cookies after a user declined
- SSR output differs from client state after reload or hard navigation
- Banner behavior changes between SPA navigation and full page refresh

**Phase to address:**
Phase 1: Consent state model and request/response contract

---

### Pitfall 2: Optional browser storage is written before consent exists

**What goes wrong:**
Theme, appearance, install-prompt dismissal, or similar preferences are written during bootstrap or first render before the user accepts. That means non-essential storage already exists by the time the banner appears.

**Why it happens:**
Preference hooks are often initialized globally for UX smoothness. In this codebase, `initializeTheme()` functions and install-prompt dismissal logic already use browser storage early, which is easy to miss during consent work.

**How to avoid:**
Audit every non-essential write path, not just cookies. Gate all optional writes behind the consent check. For boot-time defaults, use in-memory fallbacks until consent exists. If UX needs a temporary value for the current page, keep it ephemeral and do not persist it.

**Warning signs:**
- `localStorage.setItem(...)` runs in app bootstrap
- Dismiss/close handlers always persist state
- A fresh login writes `theme`, `appearance`, or install-prompt keys before any user choice
- Tests cover the banner but not side effects on first page load

**Phase to address:**
Phase 2: Storage audit and enforcement

---

### Pitfall 3: Decline breaks authentication or session continuity

**What goes wrong:**
Developers aggressively block or delete cookies and accidentally remove the Laravel session cookie, CSRF continuity, remember-me behavior, or other security-critical cookies. Users are logged out, redirected, or stuck in failed form submissions.

**Why it happens:**
The requirement says "decline non-essential cookies", and teams over-translate that into "disable cookies". In a Fortify + session-authenticated app, that is fatal.

**How to avoid:**
Explicitly classify essential cookies first. In this app, auth/session and CSRF-related browser behavior must continue working after decline. Implement deny rules as an allowlist for essential items plus a blocklist for optional ones, not as a blanket storage purge.

**Warning signs:**
- Login succeeds and the next request is anonymous
- POST actions fail with token mismatch after decline
- Users are re-prompted because the essential state path was also removed
- Decline handlers call broad cookie-clearing utilities

**Phase to address:**
Phase 1: Essential vs non-essential classification

---

### Pitfall 4: Existing optional cookies survive a later decline

**What goes wrong:**
The app correctly stops future optional writes, but cookies or `localStorage` entries created before decline remain in place. Compliance intent is missed because the browser still retains non-essential state.

**Why it happens:**
Teams focus on write prevention and forget revocation. This is common when consent is added to an existing app with prior theme/sidebar/preferences behavior.

**How to avoid:**
Treat decline as both a policy decision and a cleanup operation. Enumerate optional cookie names and storage keys, remove them when the user declines, and verify the cleanup after hard refresh and subsequent login.

**Warning signs:**
- Decline hides the banner but old preference keys remain in DevTools
- Middleware continues consuming `sidebar_state`, `theme`, or `appearance`
- QA only tests new users, not users with pre-existing browser state

**Phase to address:**
Phase 2: Revocation and cleanup

---

### Pitfall 5: Compliance scope expands into a preference-center rewrite

**What goes wrong:**
A simple accept/decline requirement turns into category toggles, policy-center UI, geo-detection, script scanners, or legal-text sprawl. Delivery slows down and the core requirement remains half-finished.

**Why it happens:**
Consent work attracts legal and product overreach. Teams mistake "be careful" for "build a fully generalized CMP".

**How to avoid:**
Hold the increment boundary. This milestone only needs immediate post-login prompting, `Accept all`, `Decline`, persistence of the decision, and strict withholding of optional storage. Capture future ideas separately instead of expanding the implementation.

**Warning signs:**
- New categories appear without a requirement change
- The UI starts offering per-feature toggles
- Engineers are debating geo-targeting before finishing storage enforcement
- Copy changes outpace implementation and tests

**Phase to address:**
Phase 0: Scope control and acceptance criteria

---

### Pitfall 6: Prompt timing is technically correct but product-wrong

**What goes wrong:**
The banner exists, but it appears late, below the fold, after other modals, or only on some authenticated pages. Users interact with optional features before seeing it, or mobile layouts obscure it.

**Why it happens:**
Teams mount the banner in a convenient component instead of the authenticated entry path. Inertia apps can mask this because SPA navigation hides timing flaws that show up on full reload or first page after login.

**How to avoid:**
Define the exact prompt entry point: first authenticated page after login when no prior decision exists. Place the UI in the shared authenticated shell, verify on desktop and mobile, and test interaction ordering against existing install/update prompts.

**Warning signs:**
- The banner is absent on the first authenticated response
- It competes with install/update modals in the same viewport region
- QA confirms it appears eventually, not immediately after login
- Some roles/layout variants show different behavior

**Phase to address:**
Phase 3: Authenticated UX integration

---

### Pitfall 7: SSR and client boot create a flash of unconsented preference state

**What goes wrong:**
The server renders one theme/state, the inline bootstrap script restores another from local storage, and the client later applies a third interpretation after consent logic runs. Users see flicker, and optional state may be applied before acceptance.

**Why it happens:**
This app already restores `theme` in `resources/views/app.blade.php` and initializes appearance/theme during client bootstrap. Consent logic layered on top can easily leave these paths inconsistent.

**How to avoid:**
Design consent-aware bootstrap rules. If no acceptance exists, SSR and boot should use safe defaults or transient in-memory values only. Remove any unconditional restoration from local storage or optional cookies before consent.

**Warning signs:**
- Theme flashes on hard refresh
- View source / Blade boot script still reads `localStorage` unconditionally
- Consent decline works after hydration but not during first paint

**Phase to address:**
Phase 2: Bootstrap and SSR alignment

---

### Pitfall 8: Middleware keeps depending on optional cookies after decline

**What goes wrong:**
Server-side behavior continues reading optional cookies such as layout/sidebar preferences, so the app still benefits from non-essential persistence even when the user declined.

**Why it happens:**
Cookie consent work often focuses on writes, not reads. Existing middleware and request-sharing logic can quietly preserve optional behavior.

**How to avoid:**
Audit server reads as well as writes. When consent is absent or declined, treat optional cookies as nonexistent. In this app, request-sharing for sidebar or other UI preferences should degrade gracefully without assuming the cookie may still be honored.

**Warning signs:**
- PHP middleware reads preference cookies regardless of consent
- Declined users still get customized layout state on reload
- Optional cookies affect SSR but not client navigation

**Phase to address:**
Phase 2: Server-side cookie consumption audit

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Keep consent state only in `localStorage` | Fast frontend implementation | Server cannot enforce or reason about consent consistently | Never |
| Gate only cookie writes, ignore `localStorage` | Small code diff | Non-essential browser storage still persists before/after decline | Never |
| Hardcode an "essential" list in multiple files | Quick local fixes | Drift between PHP, React, and tests | Only if centralized immediately in the same phase |
| Suppress the banner until after other onboarding UI | Cleaner screen at first glance | Consent timing no longer matches requirement | Never |
| Leave old optional keys in place on decline | Avoids cleanup edge cases | Revocation is incomplete and hard to trust | Never |

## Integration Gotchas

Common mistakes when connecting app layers for consent enforcement.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Laravel middleware + React consent hook | Frontend knows the choice, backend does not | Use one consent contract visible to both request handling and UI |
| Blade bootstrap + client theme hooks | Blade reads local storage or cookies unconditionally | Make first-paint theme logic consent-aware and safe by default |
| Auth redirect flow + banner display | Prompt mounts on a later page instead of first authenticated load | Trigger on the first authenticated response after login with no decision |
| Existing install/update prompts + consent banner | Multiple modals compete and obscure each other | Define display priority so consent appears first and predictably |
| SSR shared props + optional cookies | Shared props keep honoring optional cookies after decline | Ignore optional cookie-backed props unless consent allows them |

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Scattered consent checks in many hooks/components | Regressions when new preferences are added | Centralize consent helpers and storage wrappers | Breaks as soon as 3-5 independent preference features exist |
| Cleanup only in UI event handlers | Stale optional state remains for users who switch devices or browsers | Add deterministic cleanup on every relevant app boot/request path | Breaks once users revisit with mixed existing state |
| Testing only SPA navigation | Production-only bugs on hard refresh/login redirect | Include SSR, reload, and post-login integration tests | Breaks immediately in real user flows |

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| Treating session/auth cookies as non-essential | Forced logouts, broken CSRF protection, unreliable authentication | Maintain an explicit essential allowlist for auth/security cookies |
| Storing consent in a tamper-prone client-only path with no server validation | Inconsistent enforcement and weak auditability | Use a server-visible, versioned consent representation |
| Clearing cookies broadly by path/domain guesswork | Accidentally removing security-critical cookies while leaving some optional ones behind | Delete only enumerated optional keys with tested path/domain behavior |

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Banner copy implies users can reject essential auth cookies | Confusion and fear that the app is unsafe | State clearly that essential auth/security cookies stay on and why |
| Consent competes with install or update prompts | Users dismiss the wrong thing and never make a decision | Give consent highest priority on first authenticated entry |
| Decline quietly resets theme/layout without explanation | Users think the app is broken | Use stable defaults and clear copy that optional preferences are disabled unless accepted |

## "Looks Done But Isn't" Checklist

- [ ] **Consent prompt timing:** Verify it appears on the first authenticated page after login, not just on later SPA navigation.
- [ ] **Decline path:** Verify login, session continuity, CSRF-protected forms, and 2FA-related flows still work after decline.
- [ ] **Optional storage audit:** Verify no new `localStorage`, `sessionStorage`, or optional cookie writes occur before acceptance.
- [ ] **Revocation:** Verify old `theme`, `appearance`, `sidebar_state`, and install-prompt persistence are removed or ignored after decline.
- [ ] **SSR parity:** Verify hard refresh produces the same consent-aware behavior as client-side navigation.
- [ ] **Prompt priority:** Verify install/update prompts do not hide or delay consent on mobile or desktop.

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Optional storage written before consent | MEDIUM | Patch all write paths, add cleanup for existing keys, and run browser-level regression checks on fresh and stateful sessions |
| Decline breaks session/login flow | HIGH | Roll back the blocking logic, restore essential-cookie allowlist behavior, and retest full auth flows before redeploy |
| Consent state differs between server and client | MEDIUM | Introduce one canonical consent contract, migrate old client-only state, and verify SSR/hydration on hard refresh |
| Existing optional cookies survive decline | LOW | Add deterministic cleanup on decline and boot, then verify through DevTools and automated tests |

## Pitfall-to-Phase Mapping

How roadmap phases should address these pitfalls.

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Consent stored only in client storage | Phase 1: Consent state model and server/client contract | Hard refresh after choosing accept/decline preserves one consistent server-visible state |
| Optional storage writes before consent | Phase 2: Storage audit and enforcement | Fresh authenticated login produces no optional `localStorage` or cookie writes before acceptance |
| Decline breaks session behavior | Phase 1: Essential vs non-essential classification | Login, logout, CSRF-protected POSTs, and 2FA flows work after decline |
| Old optional state survives decline | Phase 2: Revocation and cleanup | Pre-seeded browser state is removed or ignored immediately after decline |
| Scope expands into CMP overbuild | Phase 0: Scope control and acceptance criteria | Phase definition stays limited to accept/decline and persistence requirements |
| Prompt shows too late or inconsistently | Phase 3: Authenticated UX integration | First authenticated response shows consent ahead of competing prompts |
| SSR/client bootstrap uses optional state before consent | Phase 2: Bootstrap and SSR alignment | Hard refresh with no consent uses safe defaults and no optional persistence |
| Middleware still consumes optional cookies | Phase 2: Server-side cookie consumption audit | Declined users do not get SSR/layout behavior driven by optional cookies |

## Sources

- `.planning/PROJECT.md`
- `.planning/codebase/CONCERNS.md`
- `.planning/codebase/STACK.md`
- Repo inspection of `resources/js/hooks/use-cookie-consent.tsx`
- Repo inspection of `resources/js/hooks/use-appearance.tsx`
- Repo inspection of `resources/js/hooks/use-theme.tsx`
- Repo inspection of `resources/js/components/install-prompt.tsx`
- Repo inspection of `resources/js/components/ui/sidebar.tsx`
- Repo inspection of `app/Http/Middleware/HandleInertiaRequests.php`
- Repo inspection of `resources/views/app.blade.php`

---
*Pitfalls research for: cookie consent in an authenticated brownfield Laravel/Inertia app*
*Researched: 2026-03-12*
