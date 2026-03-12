---
phase: 03
slug: authenticated-prompt-experience
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-12
---

# Phase 03 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest + Vitest |
| **Config file** | `phpunit.xml`, `vitest.config.ts` |
| **Quick run command** | `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx` |
| **Full suite command** | `php artisan test --compact --filter=Consent && npm test -- --run` |
| **Estimated runtime** | ~10 seconds |

---

## Sampling Rate

- **After every task commit:** Run `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx`
- **After every plan wave:** Run `php artisan test --compact --filter=Consent && npm test -- --run`
- **Before `$gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 03-01-01 | 01 | 1 | CONS-02 | feature | `php artisan test --compact tests/Feature/Auth/ConsentRecordEndpointTest.php` | ✅ plan-owned | ⬜ pending |
| 03-01-02 | 01 | 1 | CONS-03 | feature | `php artisan test --compact tests/Feature/Auth/ConsentRecordEndpointTest.php` | ✅ plan-owned | ⬜ pending |
| 03-02-01 | 02 | 2 | CONS-01 | vitest | `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx` | ✅ plan-owned | ⬜ pending |
| 03-02-02 | 02 | 2 | APPX-01 | vitest | `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx` | ✅ plan-owned | ⬜ pending |
| 03-02-03 | 02 | 2 | APPX-02 | vitest | `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx` | ✅ plan-owned | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

Existing infrastructure covers all phase requirements. Phase 3 creates new automated tests inside the execution plans; no separate Wave 0 setup plan is required.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Prompt spacing relative to the mobile install prompt in the authenticated shell | APPX-01 | Visual shell composition is easier to confirm manually than through component assertions alone | Log in on a mobile-width viewport as an undecided user and confirm both prompt surfaces remain visible and usable. |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 15s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
