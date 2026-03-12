# Plan Summary: 01-authenticated-consent-write-endpoint

## Outcome

Added a server-owned authenticated consent write seam for `accepted` and `declined` decisions. The backend now exposes one authenticated POST endpoint, validates the allowed states through a dedicated form request, delegates persistence to `RecordUserConsentAction`, and redirects back so refreshed Inertia props expose the updated shared consent contract. Wayfinder was regenerated so the frontend can import the consent action instead of hardcoding `/consent`.

## Files Changed

- `app/Http/Controllers/ConsentController.php`
- `app/Http/Requests/Consent/RecordConsentRequest.php`
- `routes/web.php`
- `resources/js/actions/App/Http/Controllers/ConsentController.ts`
- `resources/js/actions/App/Http/Controllers/index.ts`
- `tests/Feature/Auth/ConsentRecordEndpointTest.php`
- `.planning/STATE.md`

## Verification

- `php artisan wayfinder:generate --with-form` ✅
- `php artisan test --compact tests/Feature/Auth/ConsentRecordEndpointTest.php` ✅
- `php artisan test --compact --filter=ConsentRecordEndpointTest` ✅
- `composer lint` ✅

## Notes

- The endpoint uses a single route, `consent.store`, for both `accepted` and `declined`.
- The redirect remains generic with `back()`, which keeps the endpoint reusable from any authenticated page in the shared shell.
- An unrelated deleted file, `.planning/todos/pending/2026-03-12-cookie-consent.md`, was left untouched.

## Self-Check: PASSED

- All planned files were created or updated
- Verification commands passed
- No unrelated files were intentionally modified
