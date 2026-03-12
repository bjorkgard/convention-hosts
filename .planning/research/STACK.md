# Stack Research

**Domain:** cookie consent increment for an existing authenticated Laravel + Inertia + React web app
**Researched:** 2026-03-12
**Confidence:** HIGH

## Recommended Stack

This increment does not need a third-party consent platform. The project already has the right primitives:

- Laravel 12 session and cookie handling
- Inertia shared props for authenticated app-wide state
- React 19 for the consent UI
- Existing first-party hooks for appearance, theme, sidebar, and install-prompt behavior

The recommended stack is to finish this as a first-party feature with server-backed consent state and strict client-side storage gating.

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| Laravel | 12.x | Authoritative consent persistence, cookie issuance, middleware enforcement | The app already uses Laravel sessions, Fortify auth, middleware, and encrypted cookies. Consent needs server control because authenticated users must be prompted after login and because decline must stop optional cookies from being written. |
| Inertia.js (Laravel + React adapters) | Current repo stack | Deliver consent state to every authenticated page through shared props | This app is already page-prop driven. Consent is cross-cutting UI state, so `HandleInertiaRequests` is the correct seam instead of ad hoc fetches from components. |
| React | 19.2.x | Banner/modal UI and client-side gating for optional storage writes | The repo already has a consent hook and banner. Keep the UI first-party and colocated with the authenticated layout instead of embedding an external CMP runtime. |
| Browser Web Storage API | Standard platform API | Optional local persistence only after acceptance | The app already uses `localStorage` for `theme`, `appearance`, consent, and install-prompt dismissal. Keep using platform APIs, but only for non-essential data after consent. |
| Laravel session + CSRF cookies | 12.x defaults | Essential authentication and security cookies | These are required for sign-in and request protection. They are the only browser-side storage that should remain active when consent is declined. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Laravel Fortify | current repo dependency | Existing authenticated entry flow | Use it as the trigger point for “decision missing after login” behavior. Do not add a parallel auth or identity layer. |
| Pest + Laravel HTTP tests | current repo stack | Verify cookie behavior and consent persistence | Use for server assertions: shared props, cookie presence/absence, post-login redirect behavior, and reset/version migrations. |
| Vitest + RTL | current repo stack | Verify client gating and banner rendering | Use for `use-cookie-consent`, layout rendering, and “decline means no optional storage writes” behavior. |
| Wayfinder | current repo stack | Typed route/action calls from React | Use if the consent decision is posted from React to Laravel. Do not hardcode endpoints. |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| `php artisan test --compact` | Backend verification | Add focused feature tests for login, consent persistence, and cookie clearing. |
| `npx vitest run ...` | Frontend verification | Cover storage hooks, banner behavior, and layout integration. |
| `npm run types:check` | TS safety | Useful if shared Inertia props gain consent fields. |
| `composer lint` / `npm run lint` | Keep implementation aligned with repo standards | No new toolchain is needed. |

## Prescriptive Recommendation For This Increment

### 1. Make the server the source of truth

Use a persisted user-level consent record, not browser-only state, for authenticated consent decisions.

Recommended shape:

- `users.cookie_consent_status` or equivalent enum/string: `accepted` / `declined` / `null`
- `users.cookie_consent_version` integer
- `users.cookie_consent_at` timestamp

Why:

- The requirement is tied to authenticated users, not anonymous browsers.
- A pure `localStorage` decision does not follow the user across browsers/devices.
- The server must know whether it may emit optional cookies and whether the prompt should appear immediately after login.

Keep a client mirror only as a convenience cache after the server decision exists.

### 2. Use Inertia shared props to expose consent state

Add consent state to the shared authenticated payload in `HandleInertiaRequests`, for example:

- `auth.cookieConsent.status`
- `auth.cookieConsent.version`
- `auth.cookieConsent.requiresDecision`

Why:

- The app already uses shared props for auth and sidebar state.
- The authenticated layout is the correct global mounting point.
- This avoids client boot logic guessing from `localStorage`.

### 3. Keep the consent UI inside the authenticated app layout

Mount the banner/modal in the shared authenticated layout, next to other cross-cutting UI like the install prompt and toaster.

Preferred behavior:

- Only show for authenticated users
- Show immediately after login when no decision exists
- Offer only `Accept all` and `Decline`
- Make `Decline` as prominent and as easy as `Accept all`

Do not build a pre-login sitewide CMP for this increment. That is explicitly out of scope.

### 4. Treat all non-essential client persistence as blocked until acceptance

For this project, the following are non-essential and should not be persisted before acceptance:

- `theme`
- `appearance`
- `sidebar_state`
- `install-prompt-dismissed`
- any future analytics, A/B testing, or marketing identifiers

This matters because the current codebase already uses:

- `localStorage` for `theme`, `appearance`, consent, and install prompt dismissal
- cookies for `appearance`, `theme`, and `sidebar_state`
- server reads of `appearance`, `theme`, and `sidebar_state`

Under this increment, declining must block both cookies and equivalent local storage for optional preferences, not just cookies.

### 5. Move optional preference persistence behind a single policy layer

Do not leave storage decisions scattered through hooks and components.

Recommended pattern:

- one shared consent policy module for browser writes
- one Laravel-side policy for cookie issuance / clearing
- feature hooks call the policy instead of writing cookies directly

Practical consequence for this codebase:

- `use-appearance`
- `use-theme`
- sidebar persistence
- install prompt dismissal

should all route writes through the same consent gate.

### 6. On decline, clear previously written optional state

Decline should not merely stop future writes. It should also clear already-issued optional cookies and matching local storage keys.

Recommended clear list:

- `appearance` cookie + `localStorage.appearance`
- `theme` cookie + `localStorage.theme`
- `sidebar_state` cookie
- `localStorage.install-prompt-dismissed`

This is important because CNIL enforcement has explicitly focused on systems that continue reading or placing non-essential trackers after refusal.

### 7. Version the consent decision

Keep a consent version integer and invalidate older decisions if the meaning of consent changes.

Use this when:

- adding new optional storage categories later
- changing banner copy in a material way
- introducing analytics or third-party embeds in a future increment

Do not hardcode a forever-valid client-only `COOKIE_CONSENT_VERSION` without matching server semantics.

## Installation

No new package installation is recommended for this increment.

```bash
# Core
# none

# Supporting
# none

# Dev dependencies
# none
```

If implementation reveals a genuine legal-content management need later, add that separately. It is not justified by the current scope.

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| First-party Laravel + Inertia consent flow | OneTrust / Cookiebot / external CMP | Use only if the product later adds many third-party tags, multi-jurisdiction templates, cross-property consent sync, or a legal/compliance team explicitly requires a managed CMP. |
| User table consent persistence | `localStorage`-only persistence | Use only for anonymous brochure sites where persistence does not need to follow authenticated identity. That is not this app. |
| App-wide shared props + layout mount | Per-page consent checks | Use only if a feature is intentionally isolated. This increment is global and authenticated, so per-page duplication is the wrong shape. |
| Small first-party banner/modal | Full preference center with categories | Use only if product scope expands to analytics/marketing/embedding controls. Current milestone explicitly excludes granular categories. |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Consent state only in `localStorage` | It is browser-specific, invisible to Laravel on first request, and does not satisfy authenticated cross-device persistence expectations | Persist consent on the user record and share it through Inertia |
| Optional preference persistence before a choice | EDPB/CNIL guidance treats cookies and similar terminal-storage technologies broadly; `localStorage` is not a loophole | Block all non-essential client storage until acceptance |
| A third-party CMP script injected into the existing app shell | Unnecessary complexity, new runtime weight, and likely overkill for a simple accept/decline flow | Keep a small first-party React component backed by Laravel |
| Dark-pattern UI | Regulators have enforced “reject as easy as accept” expectations | Use two clear peer actions with equal effort |
| Silent fallback to theme/sidebar cookies when consent is declined | It would keep storing optional state after refusal | Keep optional preferences in memory for the current page only, or reset to defaults |
| New analytics/tag-manager work in the same increment | It expands scope and complicates the compliance surface immediately | Finish storage gating first, then add optional integrations behind the accepted path only |

## Stack Patterns By Variant

**If the user has no stored decision after login:**

- Use server-shared `requiresDecision = true`
- Mount the banner in the authenticated layout immediately
- Do not write optional cookies or local storage before a choice

**If the user accepts:**

- Persist acceptance on the user record
- Optionally mirror that state client-side for fast checks
- Re-enable optional preference persistence

**If the user declines:**

- Persist decline on the user record
- Clear optional cookies and local storage
- Continue issuing only essential auth/session/CSRF cookies

**If consent version changes later:**

- Treat old versions as undecided
- reprompt after login
- keep version comparison on both server and client

## Version Compatibility

| Package A | Compatible With | Notes |
|-----------|-----------------|-------|
| Laravel 12 session middleware | Fortify session auth | Current app already uses this combination; keep essential cookies here. |
| Inertia shared props | React 19 pages/layouts | Best fit for global authenticated consent state. |
| Web Storage API | React hooks in current repo | Fine for optional persistence after consent, but do not treat it as exempt from consent analysis. |
| Existing `use-cookie-consent` hook | Current repo UI patterns | Only if refactored so the server becomes authoritative instead of client-only storage. |

## Project-Specific Notes

Based on repo context and direct code inspection:

- `resources/js/hooks/use-cookie-consent.tsx` already exists but currently stores the decision only in `localStorage`
- `resources/js/components/cookie-consent-banner.tsx` already exists as a first-party UI
- `resources/js/hooks/use-appearance.tsx` and `resources/js/hooks/use-theme.tsx` already gate cookie writes through `canSetCookies()`, but they still initialize and persist values in `localStorage`
- `resources/js/components/ui/sidebar.tsx` gates cookie persistence with `canSetCookies()`
- `resources/js/components/install-prompt.tsx` stores dismissal in `localStorage` without consent gating
- `app/Http/Middleware/HandleAppearance.php` and `app/Http/Middleware/HandleInertiaRequests.php` still assume optional cookies may exist and currently have no server-backed consent model
- `resources/views/app.blade.php` reads `localStorage.theme` during boot, which means optional preference restoration currently bypasses any server-side consent knowledge

Inference from repo context:

- The current codebase is already close to a workable first-party consent architecture.
- The main missing pieces are server-backed persistence, consistent storage blocking, and cleanup-on-decline.

## Sources

- Repository context only:
  - `.planning/PROJECT.md`
  - `.planning/codebase/STACK.md`
  - `.planning/codebase/ARCHITECTURE.md`
  - direct code inspection of current consent/storage files in this repo
- EDPB Guidelines 2/2023 on the Technical Scope of Article 5(3) of the ePrivacy Directive: https://www.edpb.europa.eu/system/files/2023-11/edpb_guidelines_202302_technical_scope_art_53_eprivacydirective_en.pdf
  - Verified that Article 5(3) applies beyond cookies to similar technologies involving storage/access on terminal equipment.
- Directive 2002/58/EC (ePrivacy Directive), Article 5(3): https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32002L0058
  - Primary legal text for consent or strict-necessity exemption around terminal storage/access.
- CNIL, “Cookies et traceurs : comment mettre mon site web en conformité ?”: https://www.cnil.fr/fr/cookies-et-autres-traceurs/regles/cookies/comment-mettre-mon-site-web-en-conformite
  - Verified that CNIL guidance expressly covers APIs such as `localStorage` and `IndexedDB`.
- CNIL, “Refusing cookies should be as easy as accepting them”: https://www.cnil.fr/en/refusing-cookies-should-be-easy-accepting-them-cnil-continues-its-action-and-issues-new-orders
  - Verified “reject as easy as accept” enforcement direction.
- CNIL, “Dark Patterns in Cookie Banners”: https://www.cnil.fr/en/dark-patterns-cookie-banners-cnil-issues-formal-notice-website-publishers
  - Verified that misleading consent-banner design remains an enforcement focus.
- CNIL, “Cookies placed without consent: the company that publishes the website vanityfair.fr fined 750,000 euros”: https://www.cnil.fr/en/cookies-placed-without-consent-company-publishes-website-vanityfairfr-fined-750000-euros
  - Verified recent enforcement against placing or continuing to read consent-requiring trackers despite refusal.
- Laravel 12 HTTP Session docs: https://laravel.com/docs/12.x/session
  - Verified session handling and session fixation notes.
- Laravel 12 HTTP Requests docs: https://laravel.com/docs/12.x/requests#cookies
  - Verified Laravel request cookie access semantics.
- Laravel 12 HTTP Responses docs: https://laravel.com/docs/12.x/responses#attaching-cookies-to-responses
  - Verified response cookie attachment and queued-cookie patterns.
- Laravel 12 CSRF docs: https://laravel.com/docs/12.x/csrf
  - Verified framework CSRF token cookie behavior.
- MDN Web Storage API: https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API
  - Verified platform behavior for `localStorage`, `sessionStorage`, and cross-tab `storage` events.

---
*Stack research for: cookie consent increment in Convention Hosts*
*Researched: 2026-03-12*
