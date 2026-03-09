# Android Material Design 3 Theme — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a complete Android / Material Design 3 theme that auto-activates on Android devices, following the approved design in `docs/plans/2026-03-09-android-theme-design.md`.

**Architecture:** CSS-only theme scoped to `[data-theme="android"]` — mirrors the existing Apple theme pattern in `app.css`. A small UA detection function in `use-theme.tsx` auto-selects the theme on Android devices. Zero new dependencies.

**Tech Stack:** Tailwind CSS v4, OKLch color space, Radix UI `data-slot` attribute selectors, React 19 / TypeScript

---

### Task 1: Add Android color variables to app.css

**Files:**
- Modify: `resources/css/app.css` (after the Rose theme block, before the Apple theme block)

**Step 1: Locate the insertion point**

Open `resources/css/app.css`. Find the comment `/* ===` that starts the Apple theme block (around line 246). The new Android block goes immediately before it.

**Step 2: Insert the Android color variables**

Add the following block at line ~246 (before the Apple theme comment):

```css
/* ============================================================
 * Android Theme — Material Design 3 Baseline
 * Roboto system font · M3 purple tonal palette · elevation surfaces
 * ============================================================ */

/* Light mode — M3 baseline light scheme */
:root[data-theme="android"] {
    --background: oklch(0.9997 0.003 300);         /* Background — near-white + purple tint */
    --foreground: oklch(0.155 0.005 295);           /* On-background */
    --card: oklch(0.965 0.006 297);                 /* Surface-container-low */
    --card-foreground: oklch(0.155 0.005 295);      /* On-surface */
    --popover: oklch(0.965 0.006 297);              /* Surface-container */
    --popover-foreground: oklch(0.155 0.005 295);
    --primary: oklch(0.45 0.18 300);               /* Primary — #6750A4 */
    --primary-foreground: oklch(1 0 0);             /* On-primary — white */
    --secondary: oklch(0.90 0.04 295);             /* Secondary-container */
    --secondary-foreground: oklch(0.20 0.08 295);
    --muted: oklch(0.91 0.02 295);                 /* Surface-variant */
    --muted-foreground: oklch(0.40 0.02 295);       /* On-surface-variant */
    --accent: oklch(0.93 0.06 300);                /* Primary-container — #EADDFF */
    --accent-foreground: oklch(0.20 0.15 295);      /* On-primary-container */
    --destructive: oklch(0.49 0.22 27);            /* Error — #B3261E */
    --destructive-foreground: oklch(1 0 0);
    --border: oklch(0.83 0.015 295);               /* Outline-variant — #CAC4D0 */
    --input: oklch(0.935 0.015 295);               /* Surface-container-highest (filled inputs) */
    --ring: oklch(0.45 0.18 300);                  /* Primary — focus indicator */
    --radius: 0.875rem;                             /* 14px — M3 Medium shape */
    --chart-1: oklch(0.45 0.18 300);              /* purple — primary */
    --chart-2: oklch(0.55 0.17 155);              /* green */
    --chart-3: oklch(0.55 0.22 27);               /* red — error */
    --chart-4: oklch(0.65 0.19 55);               /* orange */
    --chart-5: oklch(0.55 0.12 230);              /* blue — tertiary */
    --sidebar: oklch(0.955 0.008 297);             /* Surface-container */
    --sidebar-foreground: oklch(0.155 0.005 295);
    --sidebar-primary: oklch(0.45 0.18 300);
    --sidebar-primary-foreground: oklch(1 0 0);
    --sidebar-accent: oklch(0.93 0.06 300);        /* Primary-container for active items */
    --sidebar-accent-foreground: oklch(0.20 0.15 295);
    --sidebar-border: oklch(0.83 0.015 295);
    --sidebar-ring: oklch(0.45 0.18 300);
}

/* Dark mode — M3 baseline dark scheme */
.dark[data-theme="android"] {
    --background: oklch(0.155 0.005 295);          /* Background — #1C1B1F */
    --foreground: oklch(0.905 0.01 295);            /* On-background — #E6E1E5 */
    --card: oklch(0.21 0.008 295);                 /* Surface-container-low — #211F26 */
    --card-foreground: oklch(0.905 0.01 295);
    --popover: oklch(0.24 0.008 295);              /* Surface-container */
    --popover-foreground: oklch(0.905 0.01 295);
    --primary: oklch(0.82 0.10 295);              /* Primary — #D0BCFF lavender */
    --primary-foreground: oklch(0.29 0.13 295);    /* On-primary — #381E72 */
    --secondary: oklch(0.35 0.03 295);             /* Secondary-container */
    --secondary-foreground: oklch(0.87 0.04 295);
    --muted: oklch(0.35 0.025 295);               /* Surface-variant — #49454F */
    --muted-foreground: oklch(0.81 0.015 295);     /* On-surface-variant — #CAC4D0 */
    --accent: oklch(0.38 0.14 295);               /* Primary-container — #4F378B */
    --accent-foreground: oklch(0.93 0.06 300);     /* On-primary-container */
    --destructive: oklch(0.79 0.12 27);            /* Error — #F2B8B5 */
    --destructive-foreground: oklch(0.32 0.15 27);
    --border: oklch(0.38 0.025 295);              /* Outline-variant — #49454F */
    --input: oklch(0.28 0.02 295);               /* Surface-container-highest */
    --ring: oklch(0.82 0.10 295);                 /* Primary */
    --chart-1: oklch(0.82 0.10 295);             /* lavender — primary */
    --chart-2: oklch(0.65 0.17 145);             /* green */
    --chart-3: oklch(0.79 0.12 27);              /* red */
    --chart-4: oklch(0.68 0.18 55);              /* orange */
    --chart-5: oklch(0.72 0.12 230);             /* blue */
    --sidebar: oklch(0.195 0.01 295);             /* Surface-container */
    --sidebar-foreground: oklch(0.905 0.01 295);
    --sidebar-primary: oklch(0.82 0.10 295);
    --sidebar-primary-foreground: oklch(0.29 0.13 295);
    --sidebar-accent: oklch(0.38 0.14 295);
    --sidebar-accent-foreground: oklch(0.93 0.06 300);
    --sidebar-border: oklch(0.38 0.025 295);
    --sidebar-ring: oklch(0.82 0.10 295);
}
```

**Step 3: Verify in browser**

Start the dev server (`composer dev`) and manually set `data-theme="android"` in browser DevTools on `<html>`. Confirm colors shift to purple/lavender palette.

**Step 4: Commit**

```bash
git add resources/css/app.css
git commit -m "feat(theme): add Android M3 color variables to app.css"
```

---

### Task 2: Add Android component overrides to app.css

**Files:**
- Modify: `resources/css/app.css` (immediately after the color variables from Task 1, before the Apple HIG block)

**Step 1: Insert the component overrides block**

Add immediately after the dark mode `.dark[data-theme="android"]` block:

```css
/* === Android M3 Component Overrides ===
 * All rules scoped to [data-theme="android"] — isolated from other themes.
 */

/* Roboto system font stack — canonical Android typeface */
[data-theme="android"] body {
    font-family: 'Roboto', 'Google Sans', system-ui, -apple-system, sans-serif;
    -webkit-font-smoothing: subpixel-antialiased;
    text-rendering: optimizeLegibility;
}

/* Buttons — M3 Full shape (pill) + state layer simulation */
[data-theme="android"] [data-slot="button"] {
    border-radius: 9999px;
    font-weight: 500;
    letter-spacing: 0.00625em;
    position: relative;
    overflow: hidden;
}

/* M3 state layer — hover (8% primary overlay) */
[data-theme="android"] [data-slot="button"]::after {
    content: '';
    position: absolute;
    inset: 0;
    background-color: currentColor;
    opacity: 0;
    border-radius: inherit;
    pointer-events: none;
    transition: opacity 150ms cubic-bezier(0.2, 0, 0, 1);
}
[data-theme="android"] [data-slot="button"]:hover::after {
    opacity: 0.08;
}
[data-theme="android"] [data-slot="button"]:active::after {
    opacity: 0.12;
}

/* Inputs — M3 Filled text field */
/* Top corners rounded, bottom flat, bottom-border-only indicator */
[data-theme="android"] [data-slot="input"] {
    background-color: var(--muted);
    border-color: transparent;
    border-bottom-color: var(--muted-foreground);
    border-radius: var(--radius) var(--radius) 0 0;
    height: 3.5rem;
    transition: border-color 150ms cubic-bezier(0.2, 0, 0, 1),
                border-bottom-width 150ms cubic-bezier(0.2, 0, 0, 1);
}
[data-theme="android"] [data-slot="input"]:focus-visible {
    border-color: transparent;
    border-bottom-color: var(--primary);
    border-bottom-width: 2px;
    box-shadow: none;
    outline: none;
    background-color: var(--muted);
}

/* Select trigger — same filled style */
[data-theme="android"] [data-slot="select-trigger"] {
    background-color: var(--muted);
    border-color: transparent;
    border-bottom-color: var(--muted-foreground);
    border-radius: var(--radius) var(--radius) 0 0;
    height: 3.5rem;
}

/* Cards — M3 Elevated (Level 1) */
[data-theme="android"] [data-slot="card"] {
    border: none;
    box-shadow:
        0 1px 2px oklch(0.155 0.005 295 / 0.3),
        0 2px 6px oklch(0.155 0.005 295 / 0.15);
}
.dark[data-theme="android"] [data-slot="card"] {
    border: none;
    box-shadow:
        0 1px 3px oklch(0 0 0 / 0.5),
        0 4px 8px oklch(0 0 0 / 0.25);
}

/* Header / Top App Bar — opaque, no frosted glass */
[data-theme="android"] header {
    background-color: var(--background);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    border-bottom: 1px solid var(--border);
}
.dark[data-theme="android"] header {
    background-color: var(--background);
    border-bottom: 1px solid var(--border);
}

/* Navigation Drawer (Sidebar) — opaque M3 surface-container */
[data-theme="android"] [data-sidebar="sidebar"] {
    background-color: var(--sidebar);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
.dark[data-theme="android"] [data-sidebar="sidebar"] {
    background-color: var(--sidebar);
}

/* Dropdowns / Menus — M3 Level 2 elevation */
[data-theme="android"] [data-slot="dropdown-menu-content"],
[data-theme="android"] [data-slot="popover-content"] {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    background-color: var(--popover);
    border: none;
    border-radius: var(--radius);
    box-shadow:
        0 1px 2px oklch(0.155 0.005 295 / 0.3),
        0 2px 6px oklch(0.155 0.005 295 / 0.15),
        0 4px 12px oklch(0.155 0.005 295 / 0.1);
}
.dark[data-theme="android"] [data-slot="dropdown-menu-content"],
.dark[data-theme="android"] [data-slot="popover-content"] {
    background-color: var(--popover);
    border: none;
    box-shadow:
        0 2px 4px oklch(0 0 0 / 0.5),
        0 4px 12px oklch(0 0 0 / 0.3);
}
```

**Step 2: Verify visually**

With dev server running, set the theme to Android and check:
- Inputs look like filled text fields (grey bg, only bottom border)
- Buttons have slightly more rounded (pill) shape
- Cards appear with subtle purple-tinted shadow, no border
- Header has no blur

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat(theme): add Android M3 component overrides to app.css"
```

---

### Task 3: Add Android to the theme hook and auto-detect Android devices

**Files:**
- Modify: `resources/js/hooks/use-theme.tsx`

**Step 1: Read the current file**

The current file is at `resources/js/hooks/use-theme.tsx`. Key parts to understand:
- `THEMES` array — add `'android'` here
- `THEME_LABELS` record — add `android: 'Android'` here
- `isIOSDevice()` function — add parallel `isAndroidDevice()` function
- `initializeTheme()` — update priority logic to also auto-select android

**Step 2: Apply the changes**

Make the following edits:

1. Change the `THEMES` const (line 3):
```ts
// Before:
export const THEMES = ['default', 'ocean', 'forest', 'sunset', 'rose', 'apple'] as const;

// After:
export const THEMES = ['default', 'ocean', 'forest', 'sunset', 'rose', 'apple', 'android'] as const;
```

2. Add `android` to `THEME_LABELS` (after the `apple` entry):
```ts
// Before:
export const THEME_LABELS: Record<Theme, string> = {
    default: 'Default',
    ocean: 'Ocean',
    forest: 'Forest',
    sunset: 'Sunset',
    rose: 'Rose',
    apple: 'Apple',
};

// After:
export const THEME_LABELS: Record<Theme, string> = {
    default: 'Default',
    ocean: 'Ocean',
    forest: 'Forest',
    sunset: 'Sunset',
    rose: 'Rose',
    apple: 'Apple',
    android: 'Android',
};
```

3. Add `isAndroidDevice()` after the existing `isIOSDevice()` function:
```ts
const isAndroidDevice = (): boolean => {
    if (typeof navigator === 'undefined') return false;
    return /Android/i.test(navigator.userAgent);
};
```

4. Update `initializeTheme()` to check Android before iOS:
```ts
// Before:
export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    if (!localStorage.getItem('theme')) {
        const theme = isIOSDevice() ? 'apple' : 'default';
        localStorage.setItem('theme', theme);
        setCookie('theme', theme);
    }

    currentTheme = getStoredTheme();
    applyTheme(currentTheme);
}

// After:
export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    if (!localStorage.getItem('theme')) {
        let theme: Theme = 'default';
        if (isAndroidDevice()) {
            theme = 'android';
        } else if (isIOSDevice()) {
            theme = 'apple';
        }
        localStorage.setItem('theme', theme);
        setCookie('theme', theme);
    }

    currentTheme = getStoredTheme();
    applyTheme(currentTheme);
}
```

**Step 3: Check TypeScript compiles**

```bash
npm run types:check
```

Expected: no errors.

**Step 4: Verify theme selector shows Android**

Open `/settings/theme` in the browser — the dropdown should now show "Android" as an option. Selecting it should apply the purple M3 palette.

**Step 5: Commit**

```bash
git add resources/js/hooks/use-theme.tsx
git commit -m "feat(theme): add Android theme with auto-detection for Android devices"
```

---

### Task 4: Final visual QA pass

**No files to modify — this is a verification task.**

**Step 1: Run the full test suite**

```bash
php artisan test --compact
npm test
```

Expected: all tests pass (the theme changes are CSS/TS only, no backend logic touched).

**Step 2: Verify light mode**

In the browser at `/settings/theme`, select Android. Confirm:
- Purple/lavender palette visible
- Inputs have grey filled background with bottom indicator only
- Cards have subtle shadow, no border
- Header is opaque (no blur)
- Sidebar has purple-tinted opaque background

**Step 3: Verify dark mode**

Switch to dark mode in `/settings/appearance`. Confirm:
- Background is near-black with subtle purple tone (`#1C1B1F` equivalent)
- Primary color shifts to soft lavender (`#D0BCFF` equivalent)
- Cards visible against background via tonal elevation
- All text remains legible

**Step 4: Verify other themes unaffected**

Switch back to each existing theme (Default, Ocean, Forest, Sunset, Rose, Apple) and confirm they look exactly as before.

**Step 5: Verify auto-detection simulation**

In browser DevTools console, run:
```js
Object.defineProperty(navigator, 'userAgent', {
    value: 'Mozilla/5.0 (Linux; Android 13; Pixel 7) ...',
    configurable: true
});
localStorage.removeItem('theme');
location.reload();
```

Confirm the page loads with the Android theme active.

**Step 6: Final commit with changelog note**

```bash
git add -p   # stage any minor tweaks from QA
git commit -m "feat(theme): Android Material Design 3 theme complete"
```

---

## Summary of Changes

| File | Lines changed | Nature |
|------|---------------|--------|
| `resources/css/app.css` | ~120 lines added | Color variables + component overrides |
| `resources/js/hooks/use-theme.tsx` | ~15 lines changed | THEMES array, labels, UA detection, init logic |
| `docs/plans/2026-03-09-android-theme-design.md` | New file | Design document |
| `docs/plans/2026-03-09-android-theme.md` | New file | This plan |
