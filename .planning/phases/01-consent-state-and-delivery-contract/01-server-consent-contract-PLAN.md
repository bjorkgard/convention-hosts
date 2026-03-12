---
wave: 1
depends_on: []
files_modified:
  - database/migrations/*add_*consent*_to_users_table*.php
  - config/consent.php
  - app/Models/User.php
  - app/Actions/Consent/RecordUserConsentAction.php
  - app/Support/Consent/UserConsentResolver.php
  - tests/Unit/Actions/Consent/RecordUserConsentActionTest.php
  - tests/Unit/Support/UserConsentResolverTest.php
autonomous: true
requirements:
  - CONS-04
---

# Plan 01: Server Consent Contract

## Objective

Define the authoritative Laravel-side consent record for authenticated users so consent persists across future authenticated sessions until the server-owned consent version is reset or invalidated.

## Must Haves

- Consent is persisted on `users`, not in browser storage and not in a separate consent table.
- The server owns the current consent policy version from Laravel configuration.
- A minimal backend write seam records authenticated consent decisions onto the authoritative user record.
- Resolver output normalizes to `undecided`, `accepted`, or `declined`.
- Missing, malformed, or version-mismatched stored data resolves to `undecided`.
- Resolver output includes a server-derived `allowOptionalStorage` flag for later phases.
- This plan does not add storage cleanup/enforcement work or consent prompt UX.

## Tasks

1. Add consent columns to `users` with names and types that support explicit persisted decisions plus timestamp metadata, while allowing legacy rows with no decision to continue working.
2. Add `config/consent.php` with a single backend-owned current policy version and any narrow constants needed to avoid duplicating version logic in TypeScript.
3. Update [app/Models/User.php](/Users/nathanael/Herd/Convention-Hosts/app/Models/User.php) with only the casts or thin helpers needed for the new consent fields.
4. Implement a minimal backend write seam such as [app/Actions/Consent/RecordUserConsentAction.php](/Users/nathanael/Herd/Convention-Hosts/app/Actions/Consent/RecordUserConsentAction.php) that records an authenticated user decision by writing:
   - consent state as `accepted` or `declined`
   - the current server policy version
   - `decided_at` on initial decision creation
   - `updated_at` on every decision write
   - no prompt UI or button-flow coupling, so Phase 3 can call the contract later
5. Implement a dedicated Laravel resolver or presenter at [app/Support/Consent/UserConsentResolver.php](/Users/nathanael/Herd/Convention-Hosts/app/Support/Consent/UserConsentResolver.php) that:
   - reads raw user consent fields
   - compares stored version to configured current version
   - collapses missing, malformed, and invalidated records to `undecided`
   - derives `allowOptionalStorage`
   - serializes `decidedAt` and `updatedAt` consistently for Inertia delivery
6. Add unit coverage in [tests/Unit/Actions/Consent/RecordUserConsentActionTest.php](/Users/nathanael/Herd/Convention-Hosts/tests/Unit/Actions/Consent/RecordUserConsentActionTest.php) for:
   - initial accepted write persists state, current version, and both timestamps
   - overwrite from one explicit decision to another updates state, version, and `updated_at` while preserving or intentionally re-baselining `decided_at` per the chosen contract
   - a previously written decision resolves back to `undecided` after a configured version bump
7. Add unit coverage in [tests/Unit/Support/UserConsentResolverTest.php](/Users/nathanael/Herd/Convention-Hosts/tests/Unit/Support/UserConsentResolverTest.php) for:
   - no stored consent
   - accepted current version
   - declined current version
   - version mismatch invalidation
   - malformed stored state fallback
8. Confirm existing authentication-oriented factory usage still works after the schema change without requiring unrelated test fixture rewrites.

## Verification Criteria

- Action and resolver tests prove that consent can be recorded server-side, persists across authenticated sessions, and is invalidated only by consent version mismatch in this phase.
- Writing a decision stores state, version, `decided_at`, and `updated_at` on the user record without introducing prompt-specific UI coupling.
- `accepted` resolves with `allowOptionalStorage=true`; `declined` and `undecided` resolve with `allowOptionalStorage=false`.
- A user with no consent row data still resolves safely to `undecided`.
- No task in this plan modifies prompt components, storage policy hooks, or browser-only enforcement seams.

## Automated Verification

- `php artisan test --compact tests/Unit/Actions/Consent/RecordUserConsentActionTest.php`
- `php artisan test --compact tests/Unit/Support/UserConsentResolverTest.php`
- `php artisan test --compact --filter=Consent`
- `composer lint`

## Notes For Execution

- Store explicit accepted/declined decisions; let the resolver produce `undecided` when the stored record is absent or invalidated.
- Keep the write seam backend-only in this phase; prompt endpoints and UI triggers remain deferred even though the action contract exists.
- Keep the contract seam reusable by later Phase 2 and Phase 3 work instead of spreading consent interpretation across model accessors.
