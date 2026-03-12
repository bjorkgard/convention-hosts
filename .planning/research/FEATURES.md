# Feature Research

**Domain:** authenticated cookie consent flow
**Researched:** 2026-03-12
**Confidence:** HIGH

## Feature Landscape

### Table Stakes (Users Expect These)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Immediate post-login consent prompt for undecided users | Logged-in products are expected to ask at first authenticated entry, not hide consent in settings | MEDIUM | Best fit is shared authenticated layout or shared Inertia props so every authenticated entry point behaves consistently |
| Simple `Accept all` / `Decline` decision | Users expect a fast binary choice when granular preferences are explicitly out of scope | LOW | Keep copy direct; no category matrix, no secondary flow |
| Persistent consent decision | Users expect not to be asked on every page load or later login after deciding | MEDIUM | Persist server-side or in an essential consent record so decision survives sessions and devices where intended |
| Essential-only operation after decline | Declining must still allow auth, security, and core product use without non-essential cookies | HIGH | Requires auditing theme, sidebar, install prompt, analytics, or any browser storage currently treated as convenience state |
| No non-essential storage before acceptance | Modern users expect decline to be meaningful, not cosmetic | HIGH | Includes cookies and likely localStorage/sessionStorage entries used for preferences or PWA prompt dismissal if they are non-essential |
| Easy re-check of current consent state | Users and support staff expect a visible way to verify whether consent was already set | MEDIUM | A lightweight settings surface or account notice is enough; full preference center is not needed for MVP |

### Differentiators (Competitive Advantage)

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Cross-device consent persistence for authenticated accounts | Makes the product feel coherent for staff switching devices during convention operations | MEDIUM | Strong fit for an authenticated app because identity already exists |
| Strict brownfield storage audit with graceful fallbacks | Builds trust by proving the app still works when convenience storage is unavailable | HIGH | Important because this app already has appearance and install-prompt persistence patterns |
| Consent-aware UX for optional features | Lets the product degrade cleanly instead of breaking or nagging after decline | MEDIUM | Example: theme resets to default and install prompt can reappear without creating user confusion |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Granular category toggles in v1 | Sounds more compliant and flexible | Adds policy surface, more state, and more UI complexity than this increment needs | Keep binary accept/decline now; add categories only if legal/product requirements change |
| Anonymous pre-login banner | Feels like the conventional cookie-banner pattern | Expands scope into public pages and onboarding flows not targeted by this increment | Trigger consent only after authenticated entry |
| Cookie wall that blocks app use until acceptance | Seems like a clean enforcement shortcut | Conflicts with the goal of allowing essential-only operation after decline | Allow decline and continue with essential auth/session storage only |
| Persisting preference/theme/install-dismissal after decline via localStorage | Preserves convenience UX | Makes decline meaningless because non-essential storage still occurs | Reset to defaults and keep optional state ephemeral unless consent is accepted |

## Feature Dependencies

```text
[Authenticated consent prompt]
    └──requires──> [Consent state source]
                         └──requires──> [Persistence model]

[Decline path enforcement]
    └──requires──> [Storage audit]
                         └──requires──> [Fallback behavior for optional UX]

[Settings visibility for consent]
    ──enhances──> [Persistent consent decision]

[Granular preference center]
    ──conflicts──> [Simple accept/decline MVP]
```

### Dependency Notes

- **Authenticated consent prompt requires consent state source:** the shared authenticated shell needs a single authoritative answer on whether the user is undecided, accepted, or declined.
- **Consent state source requires persistence model:** without durable persistence, the app cannot suppress repeat prompts across sessions or devices.
- **Decline path enforcement requires storage audit:** the app already persists appearance and install-related state, so all browser-facing writes must be classified essential vs non-essential before decline can be trusted.
- **Storage audit requires fallback behavior for optional UX:** once non-essential storage is blocked, optional experiences need defaults instead of broken controls or endless re-prompts.
- **Settings visibility enhances persistent consent decision:** users need a low-friction way to confirm or revisit their state later even if full granular preferences are deferred.
- **Granular preference center conflicts with simple accept/decline MVP:** it increases design, persistence, and policy complexity and dilutes the current milestone goal.

## MVP Definition

### Launch With (v1)

- [ ] Immediate prompt for authenticated users with no prior decision — core requirement for this increment
- [ ] Binary `Accept all` and `Decline` actions — keeps the flow simple and unambiguous
- [ ] Durable consent persistence tied to the authenticated user journey — prevents repeated prompting
- [ ] Enforcement that only essential auth/session storage remains after decline — makes consent meaningful
- [ ] Safe fallback behavior for non-essential preferences/storage — preserves app usability after decline

### Add After Validation (v1.x)

- [ ] Lightweight account/settings view of current consent state — add once core flow is stable and support questions appear
- [ ] Admin/support observability for consent status — add if troubleshooting or compliance reporting becomes frequent
- [ ] Consent reset/re-prompt flow for policy changes — add when terms or storage behavior materially changes

### Future Consideration (v2+)

- [ ] Granular cookie categories — defer unless legal, product, or analytics needs require it
- [ ] Pre-login consent handling for public pages and guest onboarding — separate problem space from authenticated-only scope
- [ ] Region-aware policy behavior and localization variants — only needed if compliance requirements expand materially

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Immediate authenticated consent prompt | HIGH | MEDIUM | P1 |
| Binary accept/decline action set | HIGH | LOW | P1 |
| Persistent consent state | HIGH | MEDIUM | P1 |
| Block non-essential storage on decline | HIGH | HIGH | P1 |
| Fallbacks for theme/install/sidebar convenience state | MEDIUM | MEDIUM | P1 |
| Settings visibility for current consent state | MEDIUM | MEDIUM | P2 |
| Consent reset flow | MEDIUM | MEDIUM | P2 |
| Granular categories | LOW | HIGH | P3 |

**Priority key:**
- P1: Must have for launch
- P2: Should have, add when possible
- P3: Nice to have, future consideration

## Sources

- `.planning/PROJECT.md`
- `.planning/codebase/ARCHITECTURE.md`
- Existing brownfield constraints called out in project context: Laravel session auth, shared authenticated Inertia shell, appearance middleware, and PWA/install-prompt behavior

---
*Feature research for: authenticated cookie consent increment*
*Researched: 2026-03-12*
