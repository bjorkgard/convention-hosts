# Testing Patterns

## Test Stack

- Backend tests use Pest 4 on top of PHPUnit; dependencies are declared in `composer.json`.
- Frontend tests use Vitest + Testing Library + JSDOM from `package.json`.
- `phpunit.xml` defines three backend suites:
  - `tests/Unit`
  - `tests/Feature`
  - `tests/Property`
- `tests/Pest.php` applies `Illuminate\Foundation\Testing\RefreshDatabase` to all three Pest directories by default.
- Test environment defaults in `phpunit.xml` are intentionally lightweight:
  - sqlite in-memory database
  - `MAIL_MAILER=array`
  - `QUEUE_CONNECTION=sync`
  - `SESSION_DRIVER=array`
  - `CACHE_STORE=array`

## Commands

- Backend:
  - `php artisan test --compact`
  - `php artisan test --compact --filter=TestName`
  - `php artisan test --compact tests/Feature/ConventionTest.php`
- Frontend:
  - `npm test`
  - `npx vitest run resources/js/components/conventions/__tests__/user-row.test.tsx`
- Full checks:
  - `composer ci:check`

## Backend Test Structure

## Suite Intent By Directory

- `tests/Feature` covers HTTP behavior, auth flows, Inertia props, middleware behavior, and integration-style request sequences.
- `tests/Unit` is not pure unit testing in the strict sense. Many files in `tests/Unit` still hit the database or routes, for example `tests/Unit/SearchFunctionalityTest.php` and `tests/Unit/ValidationTest.php`.
- `tests/Property` contains invariant/property-style tests, often implemented as small randomized loops instead of a dedicated property-testing library on PHP.
- `tests/Feature/Integration` contains broad scenario and performance/security audit coverage such as `tests/Feature/Integration/CompleteUserFlowsTest.php`, `tests/Feature/Integration/PerformanceTest.php`, and `tests/Feature/Integration/SecurityAuditTest.php`.
- `tests/Feature/Properties` is effectively a second property-oriented area focused on feature requirements and vertical slices.

## Common Test Setup

- `tests/TestCase.php` is minimal; most reusable behavior comes from Pest globals and helpers, not a custom base class.
- `tests/Helpers/ConventionTestHelper.php` is the main fixture builder and should be reused instead of duplicating convention/floor/section/role scaffolding.
- `ConventionTestHelper` provides reusable helpers for:
  - full convention structures
  - attaching users to conventions
  - assigning `FloorUser` and `SectionUser` scope pivots
  - returning authenticated/role-aware setup data
- Factories in `database/factories` back most test data creation.

## Assertion Patterns

- Pest’s functional style is used consistently: `it(...)`, `test(...)`, `describe(...)`, `beforeEach(...)`, `afterEach(...)`.
- Chained `expect(...)` assertions are the dominant style, often combining multiple related assertions in one chain.
- HTTP tests use standard Laravel helpers such as `actingAs(...)`, `get(...)`, `post(...)`, `patch(...)`, `delete(...)`, and route names from `route(...)`.
- Inertia page assertions are common in feature tests, for example `tests/Feature/SignedUrlVerificationTest.php`, `tests/Feature/Section/SectionAuthorizationTest.php`, and `tests/Feature/Settings/TwoFactorAuthenticationTest.php`.
- Query-count and timing assertions appear in performance-focused tests like `tests/Feature/Integration/PerformanceTest.php`.

## Mocking And Fakes

- Mail flows use `Mail::fake()` heavily; see `tests/Unit/UserInvitationFlowTest.php`, `tests/Feature/GuestConventionVerification/*`, and several property tests.
- Notifications use `Notification::fake()` in auth/password reset tests such as `tests/Feature/Auth/PasswordResetTest.php`.
- Events use `Event::fake()` in `tests/Feature/Auth/EmailVerificationTest.php`.
- Time-sensitive backend logic is tested with signed URLs and time control:
  - `URL::temporarySignedRoute(...)`
  - `Carbon::setTestNow(...)` in `tests/Unit/AttendanceReportingTest.php`
- Some tests bypass authorization globally with `Gate::before(fn () => true)`; see `tests/Unit/OccupancyUpdateTest.php` and `tests/Unit/RoleBasedAccessTest.php`.
- A few tests manually clean Mockery state with `Mockery::close()` inside loops, for example `tests/Property/EmailUpdateConfirmationTest.php`.

## Property-Style Backend Testing

- Property tests are mostly deterministic loops over randomized inputs, not shrinking-based property testing.
- The style is requirement-driven and heavily documented with comments like `Property 49` or `Validates: Requirements ...`; see `tests/Feature/Properties/CsrfProtectionPropertyTest.php`.
- Common property techniques include:
  - random-but-bounded fixture values from `fake()`
  - repeated loops (`for ($i = 0; $i < 3; $i++)`)
  - route-table introspection for middleware/security guarantees
  - cross-checking database state after each operation
- Example files:
  - `tests/Property/OccupancyPropertiesTest.php`
  - `tests/Property/ConventionPropertiesTest.php`
  - `tests/Feature/Properties/UserManagementPropertyTest.php`
  - `tests/Feature/Properties/AttendancePeriodPropertyTest.php`

## Frontend Test Structure

- Frontend tests live beside features under `resources/js/**/__tests__`.
- Observed coverage includes:
  - pages: `resources/js/pages/search/__tests__/index.test.tsx`
  - hooks: `resources/js/hooks/__tests__/*`
  - feature components: `resources/js/components/conventions/__tests__/*`
  - app-level UI: `resources/js/components/__tests__/cookie-consent-banner.test.tsx`
- Tests use `render(...)` and `renderHook(...)` from Testing Library.
- Vitest mocking is pervasive. Components typically mock:
  - `@inertiajs/react`
  - Wayfinder-generated action helpers under `@/actions/...`
  - icon packages like `lucide-react`
  - heavy child components or UI primitives
- The dominant frontend style is behavior verification at component boundaries with extensive mocking, not integrated rendering of the full app shell.

## Frontend Test Conventions

- Test files define small factory helpers like `makeConvention(...)`, `makeUser(...)`, and `makePaginatedSections(...)` for readable fixtures.
- Hook tests often mock `usePage()` and control props centrally; see `resources/js/hooks/__tests__/use-attendance-report.test.ts` and `resources/js/hooks/__tests__/use-flash-toast.test.ts`.
- Router interactions are usually asserted through mocked `router.get/post/delete` calls rather than real navigation.
- Component tests focus on accessible labels, text content, and presence/absence of controls rather than snapshots.

## Coverage Strengths

- Role-based access control has heavy backend coverage across feature, integration, and property suites.
- Signed URL and invitation/guest verification flows are tested repeatedly in both focused and integration files.
- Validation, sanitization, security headers, and CSRF guarantees have explicit tests.
- Performance regressions are monitored with query-count and elapsed-time assertions in `tests/Feature/Integration/PerformanceTest.php`.
- Occupancy and attendance logic has both action-level and request-level coverage.

## Observable Coverage Gaps

- There is no true browser E2E suite in the repository. Mobile and responsiveness checks in `tests/Feature/Integration/MobileResponsivenessTest.php` are still HTTP-level, not real browser interaction.
- Frontend coverage is selective. Many pages and flows under `resources/js/pages` have no colocated Vitest files, including major management pages like `resources/js/pages/conventions/show.tsx`, `resources/js/pages/users/index.tsx`, and `resources/js/pages/sections/show.tsx`.
- Wayfinder-generated helpers in `resources/js/actions` and `resources/js/routes` are relied on heavily but do not appear to have direct tests. That is reasonable for generated code, but route-generation regressions are only caught indirectly.
- Export behavior is tested primarily from the backend response/performance side. There is little visible test coverage for frontend export UI behavior around `resources/js/components/conventions/export-dropdown.tsx`.
- Service-layer error handling paths are lightly typed and appear to rely on string exceptions in `app/Services/AttendanceReportService.php`; tests cover business rules, but not a stronger exception contract.
- Some “unit” files behave more like feature tests, so the suite naming does not cleanly communicate isolation level. That can make test failures harder to triage.
- Authorization bypasses via `Gate::before(fn () => true)` are used in some tests. This is practical, but it means those tests do not validate the real policy/middleware stack.
- The PHP property-style tests use randomized loops without reproducible seeds or shrinking, so failures may be harder to minimize than with a dedicated property-testing tool.
- A few time-sensitive tests use real `sleep(...)`, for example `tests/Feature/SignedUrlVerificationTest.php`, which can slow the suite and introduce avoidable timing brittleness.

## Practical Guidance For New Tests

- Reuse `tests/Helpers/ConventionTestHelper.php` first for any convention-role hierarchy setup.
- Prefer named routes and Inertia assertions instead of hard-coded URLs or brittle response-text assertions.
- Keep backend tests aligned with repository style:
  - Pest syntax
  - `expect(...)` chains
  - factories + helper builders
  - explicit role assignment in setup
- For frontend tests, follow the local approach:
  - colocate under `__tests__`
  - mock Inertia and Wayfinder helpers
  - use small typed fixture factories
  - assert behavior through labels and rendered state rather than snapshots
