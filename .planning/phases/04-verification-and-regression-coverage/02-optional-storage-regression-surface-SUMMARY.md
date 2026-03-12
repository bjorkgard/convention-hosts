# Plan Summary: 02-optional-storage-regression-surface

## Outcome

Strengthened the known optional-storage regression surface without reopening a generic storage audit. Backend coverage now keeps the `appearance`, `theme`, and `sidebar_state` trust boundary explicit for accepted, declined, and undecided consent, and safe-default rendering now proves undecided users also get the same default theme, appearance, and sidebar-open behavior as declined users. The targeted frontend tests now include the missing accepted-consent contrasts so appearance, theme, sidebar persistence, and install-prompt dismissal are each proven to work when allowed and to stay inactive or cleaned up when optional storage is disallowed.

## Files Changed

- `tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php`
- `tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php`
- `resources/js/hooks/__tests__/use-appearance-consent.test.tsx`
- `resources/js/components/ui/__tests__/sidebar-consent.test.tsx`
- `resources/js/components/__tests__/install-prompt-consent.test.tsx`

## Verification

- `php artisan test --compact tests/Feature/Auth/ConsentOptionalCookieEnforcementTest.php tests/Feature/Auth/ConsentSafeDefaultRenderingTest.php` ✅
- `npx vitest run resources/js/hooks/__tests__/use-appearance-consent.test.tsx resources/js/hooks/__tests__/use-theme-consent.test.tsx resources/js/components/ui/__tests__/sidebar-consent.test.tsx resources/js/components/__tests__/install-prompt-consent.test.tsx` ✅
- `php artisan test --compact --filter=ConsentOptionalCookieEnforcementTest` ✅

## Notes

- `ConsentOptionalCookieEnforcementTest` now uses a small local helper so the accepted, declined, and undecided assertions stay on the same cookie surface without repeating the request setup.
- The accepted-consent happy path was added only where it was missing; existing focused tests like `use-theme-consent.test.tsx` already covered that contrast and did not need another rewrite.
- The unrelated dirty worktree files remained untouched.

## Self-Check: PASSED

- All owned regression surfaces now show policy-driven denial and at least one accepted-consent contrast
- Targeted backend and frontend verification commands passed
- The scope stayed on optional storage and safe defaults without changing unrelated runtime code
- No unrelated files were included in the Wave 2 changes
