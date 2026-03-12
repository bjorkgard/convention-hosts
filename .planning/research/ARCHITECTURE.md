# Architecture Research

**Domain:** Cookie consent integration for authenticated Laravel + Inertia + React flows
**Researched:** 2026-03-12
**Confidence:** HIGH

## Standard Architecture

### System Overview

```text
┌──────────────────────────────────────────────────────────────────────────┐
│                           Auth Entry Layer                              │
├──────────────────────────────────────────────────────────────────────────┤
│  Fortify login page   Guest verification   Invitation activation        │
│  LoginResponse        TwoFactorLoginResponse                             │
└───────────────────────────────┬──────────────────────────────────────────┘
                                │ redirect into authenticated app
┌──────────────────────────────────────────────────────────────────────────┐
│                    Laravel Web + Inertia Contract                       │
├──────────────────────────────────────────────────────────────────────────┤
│  Middleware pipeline                                                    │
│  - session/auth/verified                                                │
│  - consent state resolver                                               │
│  - appearance/theme/sidebar shared props                                │
│  HandleInertiaRequests shares consent + allowed preference state        │
└───────────────────────────────┬──────────────────────────────────────────┘
                                │ page props + cookie policy
┌──────────────────────────────────────────────────────────────────────────┐
│                      Authenticated React Shell                           │
├──────────────────────────────────────────────────────────────────────────┤
│  AppSidebarLayout                                                       │
│  - CookieConsentBanner / gate                                           │
│  - AppShell / SidebarProvider                                           │
│  - InstallPrompt                                                        │
│  - child Inertia pages                                                  │
└───────────────────────────────┬──────────────────────────────────────────┘
                                │ consent decisions + preference updates
┌──────────────────────────────────────────────────────────────────────────┐
│                       Client Preference Adapters                         │
├──────────────────────────────────────────────────────────────────────────┤
│  useCookieConsent   useAppearance   useTheme   sidebar cookie writer    │
│  install prompt dismissal                                               │
└───────────────────────────────┬──────────────────────────────────────────┘
                                │ essential vs non-essential storage
┌──────────────────────────────────────────────────────────────────────────┐
│                         Persistence Boundaries                           │
├──────────────────────────────────────────────────────────────────────────┤
│  Essential: Laravel session/auth/XSRF cookies                           │
│  Non-essential: appearance, theme, sidebar, install-dismissal, future   │
│  analytics/preferences                                                  │
│  Consent record should be server-backed and mirrored to client props     │
└──────────────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| `LoginResponse` / `TwoFactorLoginResponse` | Redirect authenticated users into the main app without embedding consent UI into Fortify forms | Keep current redirect behavior, but ensure first authenticated page receives consent props |
| Consent resolver middleware | Determine whether the user has an existing decision and which storage classes are allowed | New middleware/service in Laravel, placed before Inertia shared props |
| `HandleInertiaRequests` | Publish the consent contract to React on every authenticated request | Share `cookieConsent` and storage capability flags in global props |
| `AppSidebarLayout` | Mount the consent UI once for the authenticated shell | Render banner/modal above child pages so every post-login destination behaves the same |
| Client preference hooks | Read/write appearance, theme, sidebar, install-dismissal only when permitted | Centralize writes behind consent-aware helpers instead of ad hoc `localStorage`/cookie writes |
| Server cookie policy | Enforce what cookies may be read or persisted for SSR-affecting preferences | Refuse to treat optional cookies as authoritative when consent is declined or absent |

## Recommended Project Structure

```text
app/
├── Http/
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php
│   │   ├── HandleAppearance.php
│   │   └── ResolveCookieConsent.php        # new consent contract resolver
│   └── Controllers/
│       └── Settings/                       # future reset/manage endpoints
├── Support/
│   └── CookieConsent/
│       ├── ConsentState.php                # typed policy object / DTO
│       ├── ConsentStorage.php              # read/write persistence adapter
│       └── PreferenceStoragePolicy.php     # essential vs optional checks
resources/js/
├── components/
│   └── cookie-consent-banner.tsx
├── hooks/
│   ├── use-cookie-consent.tsx
│   ├── use-appearance.tsx
│   └── use-theme.tsx
├── layouts/app/
│   └── app-sidebar-layout.tsx
└── lib/
    └── preference-storage.ts              # optional shared browser helper
```

### Structure Rationale

- `app/Http/Middleware`: consent must participate in request-time decisions before shared props and SSR preference reads are computed.
- `app/Support/CookieConsent`: keeps policy logic out of controllers and React hooks, which matters once more storage-backed features are added.
- `resources/js/layouts/app`: the authenticated layout is the stable mounting point for post-login prompting.
- `resources/js/hooks` or `resources/js/lib`: optional client storage should flow through one adapter layer so future features cannot bypass consent accidentally.

## Architectural Patterns

### Pattern 1: Server-Authoritative Consent Contract

**What:** Treat consent as a server-known capability, not only a client-side `localStorage` flag.
**When to use:** Any time SSR, shared props, middleware, or future backend-driven scripts depend on optional browser storage.
**Trade-offs:** Adds one backend state path, but removes drift between client-only consent and server-side cookie usage.

**Example:**
```php
// HandleInertiaRequests.php
'cookieConsent' => [
    'status' => $consentState->status, // undecided|accepted|declined
    'canUsePreferenceStorage' => $consentState->allowsPreferences(),
]
```

### Pattern 2: Shell-Level Prompting

**What:** Render the prompt in the shared authenticated layout instead of individual pages or the login screen.
**When to use:** When the requirement is "immediately after login" but users may land on different authenticated routes.
**Trade-offs:** One consistent mount point; the layout must avoid trapping unrelated flows or duplicating prompts.

**Example:**
```tsx
export default function AppSidebarLayout({ children }: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <CookieConsentBanner />
            {children}
        </AppShell>
    );
}
```

### Pattern 3: Policy-Wrapped Preference Storage

**What:** Make appearance/theme/sidebar/install prompt writers depend on a single `canUsePreferenceStorage` decision.
**When to use:** Whenever multiple hooks/components currently write cookies or `localStorage` directly.
**Trade-offs:** Slight refactor cost now; much lower risk of future accidental non-essential storage.

**Example:**
```tsx
if (consent.canUsePreferenceStorage) {
    storage.set('theme', newTheme);
} else {
    storage.clearOptionalKeys();
}
```

## Data Flow

### Request Flow

```text
[User submits login]
    ↓
[Fortify auth succeeds]
    ↓
[LoginResponse redirect]
    ↓
[Authenticated GET /conventions...]
    ↓
[ResolveCookieConsent middleware]
    ↓
[HandleAppearance / HandleInertiaRequests]
    ↓
[AppSidebarLayout mounts]
    ↓
[Cookie consent banner decides show/hide from shared props]
```

### State Management

```text
[Server consent record]
    ↓ shared via Inertia
[useCookieConsent]
    ↓
[Banner actions]
    ↓
[POST/PATCH consent endpoint]
    ↓
[Server updates consent + clears/permits optional storage]
    ↓
[Subsequent requests and hooks use the same policy]
```

### Key Data Flows

1. **First login after no prior choice:** authentication succeeds, redirect lands in authenticated shell, server shares `undecided`, banner appears immediately, user decision is persisted once and reused.
2. **Decline path enforcement:** server continues issuing essential session/auth cookies, but optional preference cookies are ignored/cleared and client adapters stop writing optional `localStorage` keys.
3. **Accept path enablement:** preference hooks and future optional features may resume writing cookies/localStorage, and SSR may honor appearance/theme cookies again.
4. **Future reset/versioning:** a settings action or consent-version bump invalidates prior consent and the shell re-prompts without touching auth continuity.

## Build-Order Implications

1. Add a server-side consent model/contract first. Without this, the current client-only `cookie_consent` record cannot control middleware, SSR, or future backend integrations.
2. Thread consent through middleware and `HandleInertiaRequests` before mounting UI. The layout needs a reliable prop contract, not independent browser reads.
3. Mount the prompt in `resources/js/layouts/app/app-sidebar-layout.tsx`. This satisfies "immediately after login" across all authenticated destinations, including single-convention redirects and two-factor completion.
4. Refactor storage writers next. `use-appearance`, `use-theme`, `SidebarProvider`, and `InstallPrompt` currently write optional browser state independently and should move behind one policy.
5. Add cleanup behavior for decline and consent reset. Existing optional cookies/localStorage entries should be removed when a user declines after previously accepting.
6. Only after the above, add future consumers such as analytics, A/B testing, or richer settings screens. They should plug into the same consent capability check.

## Integration Points

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Fortify response ↔ authenticated app shell | Redirect + next request props | Do not embed consent state into login page form flow; use first authenticated render |
| Consent resolver ↔ `HandleInertiaRequests` | Direct service/DTO call | Shared props should be the canonical frontend contract |
| Consent policy ↔ `HandleAppearance` | Direct policy check | SSR appearance/theme cookies should be ignored when consent is absent/declined |
| Consent policy ↔ React hooks/components | Shared props + helper module | Prevent every hook from inventing its own storage rules |
| Settings/reset UI ↔ consent persistence | Standard controller/action route | Needed for future extensibility and consent version migrations |

### Storage Classes

| Storage | Classification | Architectural rule |
|---------|----------------|--------------------|
| Laravel session cookie | Essential | Always allowed for authenticated continuity |
| XSRF/auth-related cookies | Essential | Always allowed for security |
| `appearance` cookie/localStorage | Non-essential | Only write/read as preference when consent accepted |
| `theme` cookie/localStorage | Non-essential | Same as above |
| `sidebar_state` cookie | Non-essential | Default open state should fall back server-side when not allowed |
| `install-prompt-dismissed` localStorage | Non-essential | Suppress persistence when declined; allow repeated prompt or session-only behavior |
| Future analytics/tracking cookies | Non-essential | Must plug into same server-authoritative consent gate |

## Anti-Patterns

### Anti-Pattern 1: Client-Only Consent Enforcement

**What people do:** Store consent only in `localStorage` and let hooks decide independently.
**Why it's wrong:** Laravel middleware and SSR still operate without that knowledge, so optional cookies can still influence server-rendered state and future backend code can bypass the policy.
**Do this instead:** Persist or resolve consent server-side and publish one shared contract to the client.

### Anti-Pattern 2: Page-Level Consent Banners

**What people do:** Add the banner to one or two pages, or to the login page itself.
**Why it's wrong:** Post-login destinations vary (`/conventions`, single-convention redirect, two-factor flow), so prompts become inconsistent or easy to miss.
**Do this instead:** Mount once in the authenticated shell layout.

### Anti-Pattern 3: Treating Preference Cookies as Essential

**What people do:** Keep appearance/theme/sidebar cookies because they feel harmless.
**Why it's wrong:** The project requirement explicitly says decline must leave only essential auth/session cookies.
**Do this instead:** Classify all UI preference persistence as optional and clear/ignore it on decline.

## Future Extensibility

- Use a versioned consent record. The existing frontend already has `COOKIE_CONSENT_VERSION`; keep versioning in the shared server contract too so policy changes can trigger re-consent safely.
- Reserve room for more states without changing the layout boundary. `undecided`, `accepted`, and `declined` are enough now, but a later settings page or audit timestamp can extend the DTO cleanly.
- Keep persistence backend swappable. A signed cookie is enough for this increment, but a database-backed per-user preference becomes attractive if consent must follow the account across devices.
- Centralize storage classification. Future optional browser features should register against the same policy instead of adding new ad hoc allowlists.

## Sources

- `.planning/PROJECT.md`
- `.planning/codebase/ARCHITECTURE.md`
- `.planning/codebase/CONCERNS.md`
- `app/Providers/FortifyServiceProvider.php`
- `app/Http/Responses/LoginResponse.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Middleware/HandleAppearance.php`
- `resources/js/layouts/app/app-sidebar-layout.tsx`
- `resources/js/components/cookie-consent-banner.tsx`
- `resources/js/hooks/use-cookie-consent.tsx`
- `resources/js/hooks/use-appearance.tsx`
- `resources/js/hooks/use-theme.tsx`
- `resources/js/components/ui/sidebar.tsx`
- `resources/js/components/install-prompt.tsx`

---
*Architecture research for: cookie consent integration in Convention Hosts*
*Researched: 2026-03-12*
