---
phase: 02
slug: storage-enforcement-and-safe-defaults
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-12
---

# Phase 02 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 4 / PHPUnit / Vitest |
| **Config file** | `phpunit.xml`, `vitest.config.ts` |
| **Quick run command** | `php artisan test --compact --filter=Consent` |
| **Full suite command** | `php artisan test --compact && npm test -- --run` |
| **Estimated runtime** | ~60 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Consent`
- **After every plan wave:** Run `php artisan test --compact && npm test -- --run`
- **Before `$gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 01 | 1 | STOR-01 | feature | `php artisan test --compact --filter=Consent` | ❌ W0 | ⬜ pending |
| 02-01-02 | 01 | 1 | STOR-02 | unit | `npm test -- --run` | ❌ W0 | ⬜ pending |
| 02-02-01 | 02 | 2 | STOR-03 | feature | `php artisan test --compact --filter=Consent` | ❌ W0 | ⬜ pending |
| 02-02-02 | 02 | 2 | STOR-04 | unit | `npm test -- --run` | ❌ W0 | ⬜ pending |
| 02-03-01 | 03 | 2 | APPX-03 | feature | `php artisan test --compact --filter=Consent` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php` — server-side cookie trust/forget coverage for declined consent
- [ ] `resources/js/hooks/__tests__/use-appearance-consent.test.tsx` — appearance safe-default and no-persist coverage
- [ ] `resources/js/hooks/__tests__/use-theme-consent.test.tsx` — theme safe-default and no-persist coverage
- [ ] `resources/js/components/ui/__tests__/sidebar-consent.test.tsx` — sidebar no-persist and fallback behavior coverage
- [ ] `resources/js/components/__tests__/install-prompt-consent.test.tsx` — install-prompt dismissal no-persist behavior coverage

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Previously stored theme/sidebar/install-prompt state is cleared immediately when an authenticated user declines consent in a real browser session | STOR-03 | Browser storage clearing and hydration timing are best confirmed once manually in addition to automated coverage | Start from a browser session with stored optional state, decline consent, refresh, and confirm the app uses safe defaults and the known optional keys/cookies are gone |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
