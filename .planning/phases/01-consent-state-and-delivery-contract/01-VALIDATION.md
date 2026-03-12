---
phase: 01
slug: consent-state-and-delivery-contract
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-12
---

# Phase 01 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 4 / PHPUnit |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact --filter=Consent` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=Consent`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `$gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | CONS-04 | feature | `php artisan test --compact --filter=Consent` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01 | 1 | CONS-04 | unit | `php artisan test --compact --filter=Consent` | ❌ W0 | ⬜ pending |
| 01-02-01 | 02 | 1 | CONS-04 | feature | `php artisan test --compact --filter=AuthenticationTest` | ✅ | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Unit/Support/UserConsentResolverTest.php` — resolver contract coverage for state, version mismatch, and fallback behavior
- [ ] `tests/Feature/Auth/ConsentSharedPropTest.php` — authenticated shared-prop coverage for first-page delivery
- [ ] `tests/Feature/Auth/ConsentLoginFlowTest.php` — post-login consent contract delivery coverage

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Shared consent contract appears on the first authenticated destination after a real browser login | CONS-04 | Confirms integration with actual redirect/hydration path, beyond test doubles | Log in through the browser, land on the first authenticated page, and confirm the app receives the resolved consent contract without relying on localStorage |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
