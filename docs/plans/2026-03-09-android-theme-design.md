# Android Material Design 3 Theme — Design Document

**Date:** 2026-03-09
**Status:** Approved

---

## Goal

Add a complete Android / Material Design 3 theme that auto-activates on Android devices, following Google's M3 HIG with correct color system, typography, elevation model, and component shapes — while leaving all existing themes and functionality untouched.

---

## Approach

CSS-only implementation scoped entirely to `[data-theme="android"]`. Mirrors the existing Apple theme architecture. Zero new npm dependencies. All component overrides use `data-slot` attribute selectors already present in Radix UI primitives.

---

## Color System — Material Design 3 Baseline Purple

### Light mode

| CSS Variable        | Value                        | M3 Semantic Role            |
|---------------------|------------------------------|-----------------------------|
| `--background`      | `oklch(0.9997 0.003 300)`   | Background (near-white + purple tint) |
| `--foreground`      | `oklch(0.155 0.005 295)`    | On-background               |
| `--card`            | `oklch(0.965 0.006 297)`    | Surface-container-low       |
| `--card-foreground` | `oklch(0.155 0.005 295)`    | On-surface                  |
| `--popover`         | `oklch(0.965 0.006 297)`    | Surface-container           |
| `--popover-foreground` | `oklch(0.155 0.005 295)` | On-surface                  |
| `--primary`         | `oklch(0.45 0.18 300)`      | Primary (`#6750A4`)         |
| `--primary-foreground` | `oklch(1 0 0)`            | On-primary (white)          |
| `--secondary`       | `oklch(0.90 0.04 295)`      | Secondary-container         |
| `--secondary-foreground` | `oklch(0.20 0.08 295)` | On-secondary-container      |
| `--muted`           | `oklch(0.91 0.02 295)`      | Surface-variant             |
| `--muted-foreground`| `oklch(0.40 0.02 295)`      | On-surface-variant          |
| `--accent`          | `oklch(0.93 0.06 300)`      | Primary-container (`#EADDFF`) |
| `--accent-foreground` | `oklch(0.20 0.15 295)`    | On-primary-container        |
| `--destructive`     | `oklch(0.49 0.22 27)`       | Error (`#B3261E`)           |
| `--destructive-foreground` | `oklch(1 0 0)`       | On-error (white)            |
| `--border`          | `oklch(0.83 0.015 295)`     | Outline-variant (`#CAC4D0`) |
| `--input`           | `oklch(0.935 0.015 295)`    | Surface-container-highest (filled inputs) |
| `--ring`            | `oklch(0.45 0.18 300)`      | Primary (focus indicator)   |
| `--radius`          | `0.875rem` (14px)           | M3 Medium shape             |
| `--sidebar`         | `oklch(0.955 0.008 297)`    | Surface-container           |

### Dark mode

| CSS Variable        | Value                        | M3 Semantic Role            |
|---------------------|------------------------------|-----------------------------|
| `--background`      | `oklch(0.155 0.005 295)`    | Background (`#1C1B1F`)      |
| `--foreground`      | `oklch(0.905 0.01 295)`     | On-background (`#E6E1E5`)   |
| `--card`            | `oklch(0.21 0.008 295)`     | Surface-container-low (`#211F26`) |
| `--primary`         | `oklch(0.82 0.10 295)`      | Primary (`#D0BCFF` lavender) |
| `--primary-foreground` | `oklch(0.29 0.13 295)`   | On-primary (`#381E72`)      |
| `--secondary`       | `oklch(0.35 0.03 295)`      | Secondary-container         |
| `--secondary-foreground` | `oklch(0.87 0.04 295)` | On-secondary-container      |
| `--muted`           | `oklch(0.35 0.025 295)`     | Surface-variant (`#49454F`) |
| `--muted-foreground`| `oklch(0.81 0.015 295)`     | On-surface-variant (`#CAC4D0`) |
| `--accent`          | `oklch(0.38 0.14 295)`      | Primary-container (`#4F378B`) |
| `--accent-foreground` | `oklch(0.93 0.06 300)`    | On-primary-container        |
| `--destructive`     | `oklch(0.79 0.12 27)`       | Error (`#F2B8B5`)           |
| `--destructive-foreground` | `oklch(0.32 0.15 27)` | On-error-container         |
| `--border`          | `oklch(0.38 0.025 295)`     | Outline-variant (`#49454F`) |
| `--input`           | `oklch(0.28 0.02 295)`      | Surface-container-highest   |
| `--ring`            | `oklch(0.82 0.10 295)`      | Primary                     |
| `--sidebar`         | `oklch(0.195 0.01 295)`     | Surface-container           |

---

## Typography — Roboto

```css
font-family: 'Roboto', 'Google Sans', system-ui, -apple-system, sans-serif;
-webkit-font-smoothing: subpixel-antialiased;   /* Android-style rendering */
text-rendering: optimizeLegibility;
```

---

## Component Overrides

### Buttons
- Shape: `border-radius: 9999px` (M3 Full shape — pill)
- Height: `2.5rem` (40dp)
- Font: 500 weight, `letter-spacing: 0.00625em`
- Hover: 8% primary color overlay via `::after` pseudo-element
- Active: 12% overlay — simulates ripple press state

### Inputs — Filled Text Field
- Background: `var(--muted)` (surface-variant)
- Border: transparent on all sides except bottom (`1px solid var(--muted-foreground)`)
- Border-radius: top corners only (`var(--radius) var(--radius) 0 0`)
- Height: `3.5rem` (56dp — M3 field height)
- Focus: bottom border becomes `2px solid var(--primary)`, no glow/ring

### Select Trigger
- Same filled style as inputs

### Cards
- Background: `var(--card)` (tinted surface)
- No border
- Shadow (Level 1): `0 1px 2px oklch(…/0.3), 0 2px 6px oklch(…/0.15)`
- Dark: subtle shadow + tonal background (M3 dark uses tonal elevation)
- Border-radius: `var(--radius)` (14px — M3 Medium)

### Header / Top App Bar
- Background: `var(--background)` — opaque, no frosted glass
- No backdrop-filter
- Bottom: `1px solid var(--border)` separator

### Sidebar / Navigation Drawer
- Background: `var(--sidebar)` — opaque surface-container
- No blur
- Right shadow when acting as modal drawer

### Dropdowns / Menus
- Background: `var(--card)` surface-container
- No backdrop-filter
- Shadow: Level 2 elevation
- Border-radius: `var(--radius)`

---

## Android Auto-Detection

```ts
const isAndroidDevice = (): boolean => {
    if (typeof navigator === 'undefined') return false;
    return /Android/i.test(navigator.userAgent);
};
```

Priority in `initializeTheme()`:
1. Stored theme in localStorage → respect always
2. No stored preference + Android UA → `'android'`
3. No stored preference + iOS UA → `'apple'`
4. Otherwise → `'default'`

---

## Files Changed

| File | Change |
|------|--------|
| `resources/css/app.css` | Add Android color variables + component overrides |
| `resources/js/hooks/use-theme.tsx` | Add `'android'` to THEMES, add UA detection |
| (Theme settings page auto-updates from THEMES array) | No change needed |
