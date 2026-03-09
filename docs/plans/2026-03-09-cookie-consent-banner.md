# Cookie Consent Banner Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Show a bottom-fixed cookie consent banner once per device; on accept set preference cookies normally; on decline suppress all three preference cookies and fall back to localStorage-only.

**Architecture:** A `use-cookie-consent` hook manages localStorage-backed consent state with version tracking. A `canSetCookies()` guard function gates every preference cookie write in the existing hooks/components. The banner mounts in both concrete layout templates.

**Tech Stack:** React 19, TypeScript, Tailwind CSS v4, Vitest + @testing-library/react, localStorage (no new backend changes)

---

### Task 1: Create `use-cookie-consent` hook

**Files:**
- Create: `resources/js/hooks/use-cookie-consent.tsx`
- Test: `resources/js/hooks/__tests__/use-cookie-consent.test.ts`

---

**Step 1: Create the test file**

Create `resources/js/hooks/__tests__/use-cookie-consent.test.ts`:

```ts
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import {
    COOKIE_CONSENT_VERSION,
    canSetCookies,
    getCookieConsent,
    acceptCookies,
    declineCookies,
} from '../use-cookie-consent';

describe('use-cookie-consent', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        localStorage.clear();
    });

    describe('getCookieConsent', () => {
        it('returns null when no consent stored', () => {
            expect(getCookieConsent()).toBeNull();
        });

        it('returns stored consent when present and version matches', () => {
            localStorage.setItem(
                'cookie_consent',
                JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION }),
            );
            expect(getCookieConsent()).toEqual({ accepted: true, version: COOKIE_CONSENT_VERSION });
        });

        it('returns null when stored version is outdated', () => {
            localStorage.setItem(
                'cookie_consent',
                JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION - 1 }),
            );
            expect(getCookieConsent()).toBeNull();
        });

        it('returns null when stored value is malformed', () => {
            localStorage.setItem('cookie_consent', 'not-json');
            expect(getCookieConsent()).toBeNull();
        });
    });

    describe('canSetCookies', () => {
        it('returns false when no consent stored', () => {
            expect(canSetCookies()).toBe(false);
        });

        it('returns true when accepted', () => {
            acceptCookies();
            expect(canSetCookies()).toBe(true);
        });

        it('returns false when declined', () => {
            declineCookies();
            expect(canSetCookies()).toBe(false);
        });
    });

    describe('acceptCookies', () => {
        it('stores accepted consent with current version', () => {
            acceptCookies();
            const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
            expect(stored).toEqual({ accepted: true, version: COOKIE_CONSENT_VERSION });
        });
    });

    describe('declineCookies', () => {
        it('stores declined consent with current version', () => {
            declineCookies();
            const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
            expect(stored).toEqual({ accepted: false, version: COOKIE_CONSENT_VERSION });
        });
    });
});
```

**Step 2: Run the test to verify it fails**

```bash
npx vitest run resources/js/hooks/__tests__/use-cookie-consent.test.ts
```

Expected: FAIL with module not found error.

**Step 3: Create the hook**

Create `resources/js/hooks/use-cookie-consent.tsx`:

```ts
import { useCallback, useSyncExternalStore } from 'react';

export const COOKIE_CONSENT_VERSION = 1;

const STORAGE_KEY = 'cookie_consent';

export type ConsentRecord = {
    accepted: boolean;
    version: number;
};

// --- Pure helpers (no React) ---

export function getCookieConsent(): ConsentRecord | null {
    if (typeof window === 'undefined') return null;
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed: ConsentRecord = JSON.parse(raw);
        if (parsed.version !== COOKIE_CONSENT_VERSION) return null;
        return parsed;
    } catch {
        return null;
    }
}

export function canSetCookies(): boolean {
    return getCookieConsent()?.accepted === true;
}

export function acceptCookies(): void {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION }),
    );
    notify();
}

export function declineCookies(): void {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({ accepted: false, version: COOKIE_CONSENT_VERSION }),
    );
    notify();
}

// --- React hook ---

const listeners = new Set<() => void>();

function notify(): void {
    listeners.forEach((l) => l());
}

function subscribe(callback: () => void): () => void {
    listeners.add(callback);
    return () => listeners.delete(callback);
}

export type UseCookieConsentReturn = {
    readonly pending: boolean;
    readonly accepted: boolean | null;
    readonly accept: () => void;
    readonly decline: () => void;
};

export function useCookieConsent(): UseCookieConsentReturn {
    const consent = useSyncExternalStore(
        subscribe,
        () => getCookieConsent(),
        () => null,
    );

    const accept = useCallback(() => acceptCookies(), []);
    const decline = useCallback(() => declineCookies(), []);

    return {
        pending: consent === null,
        accepted: consent?.accepted ?? null,
        accept,
        decline,
    } as const;
}
```

**Step 4: Run the test to verify it passes**

```bash
npx vitest run resources/js/hooks/__tests__/use-cookie-consent.test.ts
```

Expected: All tests PASS.

**Step 5: Commit**

```bash
git add resources/js/hooks/use-cookie-consent.tsx resources/js/hooks/__tests__/use-cookie-consent.test.ts
git commit -m "feat(cookies): add use-cookie-consent hook with versioned consent storage"
```

---

### Task 2: Create the `CookieConsentBanner` component

**Files:**
- Create: `resources/js/components/cookie-consent-banner.tsx`
- Test: `resources/js/components/__tests__/cookie-consent-banner.test.tsx`

---

**Step 1: Write the failing test**

Create `resources/js/components/__tests__/cookie-consent-banner.test.tsx`:

```tsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { COOKIE_CONSENT_VERSION } from '@/hooks/use-cookie-consent';

// We test behaviour, not internal consent detail — mock the hook
vi.mock('@/hooks/use-cookie-consent', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/hooks/use-cookie-consent')>();
    return { ...actual };
});

import CookieConsentBanner from '../cookie-consent-banner';

describe('CookieConsentBanner', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        localStorage.clear();
    });

    it('renders when no consent stored', () => {
        render(<CookieConsentBanner />);
        expect(screen.getByRole('region', { name: /cookie/i })).toBeInTheDocument();
    });

    it('does not render when consent already given', () => {
        localStorage.setItem(
            'cookie_consent',
            JSON.stringify({ accepted: true, version: COOKIE_CONSENT_VERSION }),
        );
        render(<CookieConsentBanner />);
        expect(screen.queryByRole('region', { name: /cookie/i })).not.toBeInTheDocument();
    });

    it('hides after clicking Accept all', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /accept all/i }));
        expect(screen.queryByRole('region', { name: /cookie/i })).not.toBeInTheDocument();
    });

    it('hides after clicking Decline', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /decline/i }));
        expect(screen.queryByRole('region', { name: /cookie/i })).not.toBeInTheDocument();
    });

    it('stores accepted consent when accepting', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /accept all/i }));
        const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
        expect(stored.accepted).toBe(true);
    });

    it('stores declined consent when declining', async () => {
        const user = userEvent.setup();
        render(<CookieConsentBanner />);
        await user.click(screen.getByRole('button', { name: /decline/i }));
        const stored = JSON.parse(localStorage.getItem('cookie_consent')!);
        expect(stored.accepted).toBe(false);
    });
});
```

**Step 2: Run the test to verify it fails**

```bash
npx vitest run resources/js/components/__tests__/cookie-consent-banner.test.tsx
```

Expected: FAIL with module not found error.

**Step 3: Create the component**

Create `resources/js/components/cookie-consent-banner.tsx`:

```tsx
import { useCookieConsent } from '@/hooks/use-cookie-consent';

export default function CookieConsentBanner() {
    const { pending, accept, decline } = useCookieConsent();

    if (!pending) return null;

    return (
        <div
            role="region"
            aria-label="Cookie consent"
            className="fixed bottom-0 left-0 right-0 z-50 border-t border-border bg-card p-4 shadow-lg md:p-6"
        >
            <div className="mx-auto flex max-w-5xl flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div className="space-y-3 text-sm">
                    <p className="font-semibold text-foreground">We use cookies</p>
                    <p className="text-muted-foreground">
                        <span className="font-medium text-foreground">Essential cookies</span>{' '}
                        are always on: they keep you logged in and protect against cross-site
                        attacks. These are required to use the app.
                    </p>
                    <p className="text-muted-foreground">
                        <span className="font-medium text-foreground">Preference cookies</span>{' '}
                        are optional: they remember your colour theme, light/dark mode, and
                        sidebar state between visits.
                    </p>
                </div>
                <div className="flex shrink-0 gap-2">
                    <button
                        type="button"
                        onClick={decline}
                        className="rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                    >
                        Decline
                    </button>
                    <button
                        type="button"
                        onClick={accept}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Accept all
                    </button>
                </div>
            </div>
        </div>
    );
}
```

**Step 4: Run the tests to verify they pass**

```bash
npx vitest run resources/js/components/__tests__/cookie-consent-banner.test.tsx
```

Expected: All tests PASS.

**Step 5: Commit**

```bash
git add resources/js/components/cookie-consent-banner.tsx resources/js/components/__tests__/cookie-consent-banner.test.tsx
git commit -m "feat(cookies): add CookieConsentBanner component"
```

---

### Task 3: Gate preference cookies behind consent

**Files:**
- Modify: `resources/js/hooks/use-theme.tsx`
- Modify: `resources/js/hooks/use-appearance.tsx`
- Modify: `resources/js/components/ui/sidebar.tsx`

No new tests needed — the existing functionality is unchanged when accepted; the guard only suppresses cookie writes.

---

**Step 1: Update `use-theme.tsx`**

In `resources/js/hooks/use-theme.tsx`, add the import at the top:

```ts
import { canSetCookies } from '@/hooks/use-cookie-consent';
```

Wrap both `setCookie` calls:

1. In `initializeTheme()` (line ~70), change:
   ```ts
   setCookie('theme', theme);
   ```
   to:
   ```ts
   if (canSetCookies()) setCookie('theme', theme);
   ```

2. In `updateTheme` callback (line ~88), change:
   ```ts
   setCookie('theme', newTheme);
   ```
   to:
   ```ts
   if (canSetCookies()) setCookie('theme', newTheme);
   ```

**Step 2: Update `use-appearance.tsx`**

In `resources/js/hooks/use-appearance.tsx`, add the import at the top:

```ts
import { canSetCookies } from '@/hooks/use-cookie-consent';
```

Wrap both `setCookie` calls:

1. In `initializeTheme()` (line ~67), change:
   ```ts
   setCookie('appearance', 'system');
   ```
   to:
   ```ts
   if (canSetCookies()) setCookie('appearance', 'system');
   ```

2. In `updateAppearance` callback (line ~96), change:
   ```ts
   setCookie('appearance', mode);
   ```
   to:
   ```ts
   if (canSetCookies()) setCookie('appearance', mode);
   ```

**Step 3: Update `sidebar.tsx`**

In `resources/js/components/ui/sidebar.tsx`, add the import near the top:

```ts
import { canSetCookies } from '@/hooks/use-cookie-consent';
```

At line ~85, change:
```ts
document.cookie = `${SIDEBAR_COOKIE_NAME}=${openState}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
```
to:
```ts
if (canSetCookies()) {
    document.cookie = `${SIDEBAR_COOKIE_NAME}=${openState}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
}
```

**Step 4: Run all frontend tests**

```bash
npm test
```

Expected: All tests PASS.

**Step 5: Commit**

```bash
git add resources/js/hooks/use-theme.tsx resources/js/hooks/use-appearance.tsx resources/js/components/ui/sidebar.tsx
git commit -m "feat(cookies): gate preference cookie writes behind consent"
```

---

### Task 4: Mount banner in both layout templates

**Files:**
- Modify: `resources/js/layouts/app/app-sidebar-layout.tsx`
- Modify: `resources/js/layouts/auth/auth-simple-layout.tsx`

No new tests — the banner has its own tests. Visual verification is sufficient here.

---

**Step 1: Update `app-sidebar-layout.tsx`**

Add the import:
```ts
import { CookieConsentBanner } from '@/components/cookie-consent-banner';
```

Add `<CookieConsentBanner />` just before `</AppShell>`:

```tsx
export default function AppSidebarLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <UpdateNotificationModal />
            <CookieConsentBanner />
        </AppShell>
    );
}
```

**Step 2: Update `auth-simple-layout.tsx`**

Add the import:
```ts
import CookieConsentBanner from '@/components/cookie-consent-banner';
```

Add `<CookieConsentBanner />` just before the closing `</>` fragment:

```tsx
return (
    <>
        <Head>...</Head>
        <div className="relative flex min-h-svh ...">
            ...
        </div>
        <CookieConsentBanner />
    </>
);
```

**Step 3: Run all frontend tests**

```bash
npm test
```

Expected: All tests PASS.

**Step 4: Manual smoke test**

1. `composer dev`
2. Open the app in a private/incognito browser window
3. Verify the banner appears at the bottom
4. Click **Decline** — banner disappears, stays gone on reload
5. Open DevTools → Application → Local Storage → verify `cookie_consent: {"accepted":false,"version":1}`
6. Verify cookies tab has only `laravel_session` and `XSRF-TOKEN` (no `theme`, `appearance`, or `sidebar_state`)
7. Clear localStorage, reload — banner reappears
8. Click **Accept all** — banner disappears
9. Change theme in settings — verify `theme` cookie is now written

**Step 5: Commit**

```bash
git add resources/js/layouts/app/app-sidebar-layout.tsx resources/js/layouts/auth/auth-simple-layout.tsx
git commit -m "feat(cookies): mount CookieConsentBanner in app and auth layouts"
```

---

### Task 5: Final check

**Step 1: Run full test suite**

```bash
php artisan test --compact
npm test
npm run types:check
composer lint
npm run lint
```

Expected: All pass, no type errors, no lint errors.

**Step 2: Final commit (if any lint fixes needed)**

```bash
git add -p
git commit -m "chore: fix any lint/type issues from cookie consent implementation"
```
