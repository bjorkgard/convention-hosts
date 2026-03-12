# Plan Summary: 01-post-login-consent-regression

## Outcome

Expanded the authenticated consent regression coverage to follow the real Fortify login paths instead of direct authenticated page visits. The feature suite now proves that both password login and two-factor completion reach the first authenticated Inertia response with an `undecided` consent contract for the app's two supported redirect shapes: a single convention landing on `conventions/show` and multiple conventions landing on `conventions/index`. A new layout-scoped Vitest file also proves the shared authenticated shell only mounts the consent prompt while consent remains `undecided`.

## Files Changed

- `tests/Feature/Auth/ConsentLoginFlowTest.php`
- `resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx`

## Verification

- `php artisan test --compact tests/Feature/Auth/ConsentLoginFlowTest.php` ✅
- `npx vitest run resources/js/layouts/app/__tests__/app-sidebar-layout-consent.test.tsx` ✅
- `php artisan test --compact --filter=ConsentLoginFlowTest` ✅

## Notes

- The backend coverage now asserts the real redirect target before requesting the first authenticated page, which keeps the proof anchored to `LoginResponse` and `TwoFactorLoginResponse`.
- The layout test uses the real `AuthenticatedConsentPrompt` within `AppSidebarLayout` and stubs only the surrounding shell pieces so the mount-point behavior stays under test.
- The unrelated dirty files already present in the worktree were left untouched.

## Self-Check: PASSED

- All owned files were updated as planned
- Targeted verification commands passed
- The login regression now covers both supported authenticated redirect shapes for password and two-factor flows
- No unrelated files were included in the Wave 1 changes
