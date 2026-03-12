# Plan Summary: 01-server-consent-contract

## What Was Built

- Added server-owned consent persistence fields directly to `users` for explicit `accepted` and `declined` decisions, a consent policy version, and consent decision/update timestamps.
- Added `config/consent.php` with the backend-owned `current_policy_version` used as the invalidation boundary for stored decisions.
- Added `RecordUserConsentAction` as the backend write seam for authenticated consent decisions.
- Added `UserConsentResolver` to normalize raw user consent fields into the delivery contract used by later Inertia integration work.
- Added unit coverage for consent writes, version invalidation, missing consent, explicit accepted/declined decisions, and malformed stored values.

## Key Files

- `database/migrations/2026_03_12_120000_add_cookie_consent_to_users_table.php`
- `config/consent.php`
- `app/Models/User.php`
- `app/Actions/Consent/RecordUserConsentAction.php`
- `app/Support/Consent/UserConsentResolver.php`
- `tests/Unit/Actions/Consent/RecordUserConsentActionTest.php`
- `tests/Unit/Support/UserConsentResolverTest.php`

## Contract Decisions

- Persisted user values are limited to explicit `accepted` and `declined`; the resolver alone emits `undecided`.
- Resolver output returns the current server policy version, normalized state, `allowOptionalStorage`, and ISO-8601 `decidedAt` / `updatedAt` values.
- A version mismatch, malformed state, or missing consent data resolves to `undecided`.
- `consent_decided_at` is preserved when overwriting a valid current-version decision and re-baselined when the stored consent record is invalid for the active policy version.

## Verification Run

- `php artisan test --compact tests/Unit/Actions/Consent/RecordUserConsentActionTest.php` — pass
- `php artisan test --compact tests/Unit/Support/UserConsentResolverTest.php` — pass
- `php artisan test --compact --filter=Consent` — pass
- `composer lint` — pass

## Deviations

- None.
