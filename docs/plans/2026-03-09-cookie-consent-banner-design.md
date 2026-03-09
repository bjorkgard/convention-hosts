# Cookie Consent Banner — Design

**Date:** 2026-03-09
**Status:** Approved

## Overview

Add a bottom-fixed cookie consent banner that shows once per device. Users can accept or decline. Essential cookies (session/CSRF) are always set and disclosed as non-optional. Preference cookies (theme, appearance, sidebar state) are suppressed when the user declines.

## Consent Storage & Versioning

Stored in `localStorage` under the key `cookie_consent`:

```json
{ "accepted": true, "version": 1 }
```

- **`version`**: a constant (`COOKIE_CONSENT_VERSION = 1`) in `use-cookie-consent.tsx`. Bump this when new cookie functionality is introduced to force re-prompt.
- **No record** → show banner (`pending === true`).
- **Record with lower version** → show banner again (new cookies introduced).
- **Record with current version** → respect stored choice, hide banner.

On **accept**: write `{accepted: true, version: N}` — preference cookies work normally.
On **decline**: write `{accepted: false, version: N}` — preference cookies are suppressed; preferences still work via `localStorage` only (minor: no SSR pre-render of theme/appearance on hard reload, causing a brief flash until JS runs).

## UI & Components

### New files

| File | Purpose |
|------|---------|
| `resources/js/hooks/use-cookie-consent.tsx` | Read/write consent from localStorage; exports `accepted`, `pending`, `accept()`, `decline()`, `canSetCookies()` |
| `resources/js/components/cookie-consent-banner.tsx` | Banner UI component |

### Banner layout

```
┌──────────────────────────────────────────────────────────────────┐
│  We use cookies                                                  │
│                                                                  │
│  Essential cookies (always on): keep you logged in and protect   │
│  against cross-site attacks. Required to use the app.            │
│                                                                  │
│  Preference cookies (optional): remember your theme, light/dark  │
│  mode, and sidebar state between visits.                         │
│                                                                  │
│                               [Decline]  [Accept all]            │
└──────────────────────────────────────────────────────────────────┘
```

- Fixed bottom, full-width, `z-50`
- Styled with existing card/border tokens (matches app design system)
- No animation — conditionally rendered when `pending === true`
- Mounted in both `app-layout.tsx` and `auth-layout.tsx`

## Integration with Existing Cookie Code

A single guard function exported from `use-cookie-consent.tsx`:

```ts
export const canSetCookies = (): boolean => {
    try {
        const stored = localStorage.getItem('cookie_consent');
        if (!stored) return false;
        return JSON.parse(stored).accepted === true;
    } catch {
        return false;
    }
};
```

Each existing preference-cookie write is wrapped with this guard:

| File | Cookie written | Change |
|------|---------------|--------|
| `resources/js/hooks/use-theme.tsx` | `theme` | Wrap `setCookie` calls in `if (canSetCookies())` |
| `resources/js/hooks/use-appearance.tsx` | `appearance` | Wrap `setCookie` calls in `if (canSetCookies())` |
| `resources/js/components/ui/sidebar.tsx` | `sidebar_state` | Wrap `document.cookie` write in `if (canSetCookies())` |

The `initializeTheme()` / `initializeColorTheme()` calls in `app.tsx` also get this guard for the initial auto-detection (Android/iOS theme detection).

## Cookies Disclosed

| Cookie | Category | Purpose |
|--------|----------|---------|
| `laravel_session` | Essential | Maintains your login session |
| `XSRF-TOKEN` | Essential | Protects against cross-site request forgery |
| `theme` | Preference | Remembers your selected color theme |
| `appearance` | Preference | Remembers your light/dark mode preference |
| `sidebar_state` | Preference | Remembers whether the sidebar is open or closed |

## Out of Scope

- No backend changes needed — the server already handles missing preference cookies gracefully (falls back to defaults in `HandleAppearance` middleware).
- No new routes or API endpoints.
- No Wayfinder regeneration needed.
