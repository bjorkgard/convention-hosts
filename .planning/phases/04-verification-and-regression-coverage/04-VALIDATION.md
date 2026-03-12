---
phase: 04
slug: verification-and-regression-coverage
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-12
---

# Phase 04 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest + Vitest |
| **Config file** | `phpunit.xml`, `vitest.config.ts` |
| **Quick run command** | `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php` |
| **Full suite command** | `php artisan test --compact --filter=Consent && npm test -- --run` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php`
- **After every plan wave:** Run `php artisan test --compact --filter=Consent && npm test -- --run`
- **Before `$gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 20 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 04-01-01 | 01 | 1 | VERI-01 | feature | `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php` | ✅ plan-owned | ⬜ pending |
| 04-01-02 | 01 | 1 | VERI-01 | vitest | `npx vitest run resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx` | ❌ plan-owned | ⬜ pending |
| 04-02-01 | 02 | 2 | VERI-02 | feature | `php artisan test --compact tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php` | ✅ existing | ⬜ pending |
| 04-02-02 | 02 | 2 | VERI-02 | vitest | `npx vitest run resources/js/hooks/__tests__/use-appearance-consent.test.tsx resources/js/hooks/__tests__/use-theme-consent.test.tsx resources/js/components/ui/__tests__/sidebar-consent.test.tsx resources/js/components/__tests__/install-prompt-consent.test.tsx` | ✅ existing | ⬜ pending |
| 04-03-01 | 03 | 3 | VERI-03 | feature | `php artisan test --compact tests/Feature/Auth/ConsentSessionContinuityTest.php` | ❌ plan-owned | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

Existing infrastructure covers all phase requirements. Phase 4 adds new regression tests inside execution plans; no separate Wave 0 setup plan is required.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| None | — | All critical Phase 4 behaviors are expected to have automated verification | No manual-only verification required for this phase. |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 20s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
