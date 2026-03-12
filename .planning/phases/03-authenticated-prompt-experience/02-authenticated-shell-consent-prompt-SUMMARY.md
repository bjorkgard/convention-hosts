# Plan Summary: 02-authenticated-shell-consent-prompt

## Outcome

Added a server-backed authenticated consent prompt that renders from the shared authenticated shell when the server-shared consent contract is `undecided`. The prompt now offers only equal-prominence `Accept all` and `Decline` actions, posts those decisions through the generated consent controller action, and disappears through the normal Inertia refresh cycle after a decision is recorded. The legacy banner component now acts as a thin compatibility wrapper around the new prompt, and the legacy hook is explicitly marked as compatibility-only instead of the authenticated source of truth.

## Files Changed

- `resources/js/components/authenticated-consent-prompt.tsx`
- `resources/js/layouts/app/app-sidebar-layout.tsx`
- `resources/js/components/cookie-consent-banner.tsx`
- `resources/js/hooks/use-cookie-consent.tsx`
- `resources/js/components/__tests__/authenticated-consent-prompt.test.tsx`
- `resources/js/components/__tests__/cookie-consent-banner.test.tsx`
- `resources/js/hooks/__tests__/use-cookie-consent.test.ts`
- `.planning/STATE.md`

## Verification

- `npx vitest run resources/js/components/__tests__/authenticated-consent-prompt.test.tsx` ✅
- `npx vitest run resources/js/components/__tests__/cookie-consent-banner.test.tsx` ✅
- `npx vitest run resources/js/hooks/__tests__/use-cookie-consent.test.ts` ✅
- `npm run lint` ✅ with a pre-existing warning in `resources/js/components/install-prompt.tsx`

## Notes

- The prompt is mounted directly in `app-sidebar-layout.tsx`, so it is visible on authenticated pages without relying on the sidebar being opened.
- The mobile install prompt keeps its existing behavior and now gets extra top padding to avoid colliding with the consent prompt.
- `npm run lint` reordered imports in a few unrelated files outside this plan’s ownership; those incidental changes were left uncommitted.
- The unrelated deleted file `.planning/todos/pending/2026-03-12-cookie-consent.md` was left untouched.

## Self-Check: PASSED

- All owned files were created or updated as planned
- Targeted verification commands passed
- The authenticated prompt uses the server-owned consent endpoint from Wave 1
- No unrelated files were included in the plan commit
