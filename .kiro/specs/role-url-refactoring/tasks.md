# Implementation Plan: Role & URL Refactoring

## Overview

Refactor the Convention Management System from a four-tier role system (Owner, ConventionUser, FloorUser, SectionUser) to a two-tier system (Owner, Administrator) with URL-based anonymous access for floor and section management. Implementation proceeds bottom-up: database migration → models → middleware/controllers → policies/actions/services → frontend types/hooks → frontend components/pages → tests.

## Tasks

- [x] 1. Database migration and model updates
  - [x] 1.1 Create migration `simplify_roles_and_add_url_tokens`
    - Add `floor_url_token` (string 64, unique, nullable) and `section_url_token` (string 64, unique, nullable) to `conventions` table
    - Generate tokens for all existing conventions using `Str::random(64)`
    - Rename `ConventionUser` to `Administrator` in `convention_user_roles`
    - Delete all `FloorUser` and `SectionUser` entries from `convention_user_roles`
    - Drop `floor_user` and `section_user` tables
    - Remove `reported_by` column from `attendance_reports`
    - Implement reversible `down()` method: re-create pivot tables, restore `reported_by`, rename `Administrator` back to `ConventionUser`, drop token columns
    - Wrap in database transaction for atomicity
    - _Requirements: 1.5, 1.6, 1.7, 1.8, 2.5, 2.7, 8.6, 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8_

  - [x] 1.2 Update Convention model
    - Add `floor_url_token` and `section_url_token` to `$fillable`
    - Add both token fields to `$hidden` array
    - Add `booted()` method with `creating` event to auto-generate tokens via `Str::random(64)`
    - Add `floorAccessUrl()` and `sectionAccessUrl()` helper methods
    - Remove any FloorUser/SectionUser relationship methods if referenced
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 1.3 Update User model
    - Remove `floors()` BelongsToMany relationship (via `floor_user`)
    - Remove `sections()` BelongsToMany relationship (via `section_user`)
    - _Requirements: 1.5, 1.6_

  - [x] 1.4 Update AttendanceReport model
    - Remove `reported_by` from `$fillable`
    - Remove `reportedBy()` relationship method
    - _Requirements: 8.6_

  - [x] 1.5 Update Convention factory
    - Add `floor_url_token` and `section_url_token` generation to factory definition
    - _Requirements: 2.1, 2.2_

  - [ ] 1.6 Write property tests for migration and token generation (backend)
    - **Property 3: Token generation and validity on convention creation** — verify both tokens are non-null strings of at least 32 characters for any newly created convention
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
    - **Property 4: Token uniqueness across conventions** — verify floor_url_token and section_url_token differ across any two distinct conventions
    - **Validates: Requirements 2.6**
    - **Property 12: Migration role conversion correctness** — verify ConventionUser→Administrator conversion and FloorUser/SectionUser removal
    - **Validates: Requirements 10.1, 10.2, 10.3**
    - **Property 13: Migration token generation for existing conventions** — verify all pre-existing conventions get valid tokens
    - **Validates: Requirements 10.6**
    - **Property 14: Migration reversibility** — verify up+down restores schema structure
    - **Validates: Requirements 10.8**

- [ ] 2. Checkpoint - Ensure migration and model tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 3. Backend middleware, controller, and routing
  - [ ] 3.1 Create UrlAccessController
    - `floor(string $token)` method: look up convention by `floor_url_token`, store URL session in Laravel session (`convention_id`, `type: floor`, `token`), redirect to `conventions.show`
    - `section(string $token)` method: same pattern with `section_url_token` and `type: section`
    - Return 404 for invalid/non-existent tokens
    - _Requirements: 3.1, 4.1_

  - [ ] 3.2 Create EnsureConventionOrUrlAccess middleware
    - Replace `EnsureConventionAccess` middleware
    - Check if user is authenticated with a convention role → allow
    - Check if session has `url_session` with matching `convention_id` → allow
    - Otherwise abort 403
    - _Requirements: 3.1, 4.1_

  - [ ] 3.3 Update route definitions in `routes/web.php`
    - Add public routes: `GET url-access/floor/{token}` and `GET url-access/section/{token}` pointing to `UrlAccessController`
    - Replace `EnsureConventionAccess` middleware references with `EnsureConventionOrUrlAccess`
    - Remove `ScopeByRole` middleware from all route groups
    - Ensure auth-only routes (user management, convention CRUD, attendance start/stop) remain behind `auth` middleware
    - _Requirements: 3.1, 4.1_

  - [ ] 3.4 Remove ScopeByRole middleware
    - Delete `app/Http/Middleware/ScopeByRole.php`
    - Remove registration from kernel/bootstrap if applicable
    - _Requirements: 1.5, 1.6_

  - [ ] 3.5 Regenerate Wayfinder routes
    - Run `php artisan wayfinder:generate --with-form` to regenerate type-safe frontend routes after route changes
    - _Requirements: 3.1, 4.1_

  - [ ] 3.6 Write property tests for URL access flow (backend)
    - **Property 5: Floor URL session grants correct positive permissions** — verify floor token creates session allowing view floors/sections, update occupancy, report attendance
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
    - **Property 6: Floor URL session denies administrative actions** — verify floor session cannot create/edit/delete floors, create/delete sections, manage users, start/stop reports, lock periods
    - **Validates: Requirements 3.6, 3.7, 3.8, 3.9, 3.10**
    - **Property 7: Section URL session grants correct positive permissions** — verify section token creates session allowing view sections, update occupancy, report attendance
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4**
    - **Property 8: Section URL session denies administrative and floor actions** — verify section session cannot view/manage floors, create/delete sections, manage users, start/stop reports, lock periods
    - **Validates: Requirements 4.5, 4.6, 4.7, 4.8, 4.9**

- [ ] 4. Backend policies, actions, and services
  - [ ] 4.1 Update all policies
    - **ConventionPolicy**: Change `['Owner', 'ConventionUser']` → `['Owner', 'Administrator']` in `update()`
    - **FloorPolicy**: Update `create()`, `view()`, `update()`, `delete()` to check `['Owner', 'Administrator']` only; remove FloorUser branches
    - **SectionPolicy**: Simplify all methods to `['Owner', 'Administrator']`; remove FloorUser and SectionUser branches
    - **UserPolicy**: Remove FloorUser/SectionUser references; only Owner and Administrator can manage users
    - _Requirements: 1.1, 1.3, 1.4, 1.5, 1.6_

  - [ ] 4.2 Update CreateConventionAction
    - Change role assignment from `['Owner', 'ConventionUser']` to `['Owner', 'Administrator']`
    - Tokens auto-generated by Convention model boot method (no action changes needed for tokens)
    - _Requirements: 1.2, 1.3, 1.4_

  - [ ] 4.3 Update InviteUserAction
    - Remove FloorUser floor assignment logic (`floor_user` inserts)
    - Remove SectionUser section assignment logic (`section_user` inserts)
    - Only handle Owner and Administrator role assignments
    - Remove `floor_ids` and `section_ids` handling
    - _Requirements: 1.1, 1.5, 1.6, 1.9_

  - [ ] 4.4 Update AttendanceReportService
    - Remove `reported_by` parameter from `reportAttendance()`
    - Remove "only original reporter can update" restriction
    - Accept optional `?User $user` parameter (null for URL sessions)
    - Update `AttendanceReport::create()` / `updateOrCreate()` to omit `reported_by`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

  - [ ] 4.5 Update ConventionController show method
    - Pass `floor_url` and `section_url` to frontend via Inertia props (using `floorAccessUrl()` and `sectionAccessUrl()`) for Owner/Administrator only
    - Share `urlSession` data when URL session is active
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [ ] 4.6 Update HandleInertiaRequests middleware
    - Share `urlSession` prop (type and convention_id from session, or null)
    - When URL session is active, set appearance to `apple` theme
    - Update shared `userRoles` to exclude FloorUser/SectionUser options
    - _Requirements: 9.1, 9.4, 9.5_

  - [ ] 4.7 Update StoreUserRequest and UpdateUserRequest
    - Remove `floor_ids` and `section_ids` validation rules
    - Remove FloorUser/SectionUser conditional requirements
    - Update roles validation to only accept `Owner` and `Administrator`
    - _Requirements: 1.1, 1.9_

  - [ ] 4.8 Write property tests for policies and services (backend)
    - **Property 1: Role system invariant** — verify only Owner and Administrator roles exist in convention_user_roles for any convention
    - **Validates: Requirements 1.1, 1.5, 1.6**
    - **Property 2: Owner role assignment on convention creation** — verify creator always gets Owner role
    - **Validates: Requirements 1.2**
    - **Property 10: Attendance report open access** — verify any user with section permissions can create/update reports regardless of original reporter
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5**

- [ ] 5. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Frontend type and hook updates
  - [ ] 6.1 Update TypeScript types
    - **`types/user.ts`**: Change `Role` type to `'Owner' | 'Administrator'`; remove `floor_ids` and `section_ids` from `ConventionUser` interface
    - **`types/convention.ts`**: Add `floor_url?: string` and `section_url?: string` to `Convention` interface; remove `reported_by` and `reported_by_user` from `AttendanceReport` interface
    - **`types/index.ts`**: Add `UrlSession` interface (`convention_id: string`, `type: 'floor' | 'section'`); add `urlSession` to shared `PageProps`
    - _Requirements: 1.1, 2.5, 5.1, 8.6_

  - [ ] 6.2 Update `use-convention-role` hook
    - Add `isAdministrator`, `isUrlSession`, `isFloorUrlSession`, `isSectionUrlSession`, `isManager` computed properties
    - Remove `isConventionUser`, `isFloorUser`, `isSectionUser` (or whatever the current equivalents are)
    - Remove `hasFloorAccess()` and `hasSectionAccess()` if they exist
    - Read `urlSession` from page props
    - _Requirements: 1.1, 3.1, 4.1_

  - [ ] 6.3 Update `use-appearance` hook
    - Check for `urlSession` prop and force `apple` theme when URL session is active
    - _Requirements: 9.4, 9.5_

  - [ ] 6.4 Write property tests for frontend hooks (Vitest + fast-check)
    - **Property 1: Role system invariant** — generate random role arrays, verify hook only recognizes Owner/Administrator
    - **Validates: Requirements 1.1, 1.5, 1.6**
    - **Property 11: URL session Apple theme** — generate random URL session states, verify theme is always "apple" when URL session active
    - **Validates: Requirements 9.4, 9.5**

- [ ] 7. Frontend component updates
  - [ ] 7.1 Update sidebar navigation (`nav-convention.tsx`)
    - Rename "Floors" label → "Administration"
    - Rename "Search" label → "Availability"
    - Hide "Users" navigation item for URL sessions (`isUrlSession`)
    - Hide "Administration" for section URL sessions (`isSectionUrlSession`)
    - _Requirements: 7.1, 7.2, 9.1_

  - [ ] 7.2 Update user menu (`nav-user.tsx` / `app-sidebar.tsx`)
    - Hide user dropdown when `isUrlSession` is true
    - Hide profile and logout options for URL sessions
    - _Requirements: 9.1, 9.2, 9.3_

  - [ ] 7.3 Update convention show page with URL display
    - Add URL display section showing Floor Access URL and Section Access URL for Owner/Administrator
    - Add copy buttons next to each URL using `use-clipboard` hook
    - Show visual confirmation on successful copy
    - Display clear labels: "Floor Access URL", "Section Access URL"
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8, 5.9_

  - [ ] 7.4 Update `role-badge.tsx`
    - Remove FloorUser and SectionUser badge variants
    - Add/rename Administrator badge variant (replacing ConventionUser)
    - _Requirements: 1.1, 1.9_

  - [ ] 7.5 Update `user-row.tsx` and users index page
    - Remove FloorUser/SectionUser role options from role selector
    - Remove `floor_ids` and `section_ids` assignment UI
    - Only show Owner and Administrator as available roles
    - _Requirements: 1.9_

  - [ ] 7.6 Update `floor-row.tsx` and `section-card.tsx`
    - Remove user assignment icons/indicators from floor rows
    - Remove user assignment icons/indicators from section cards
    - Remove "reported by" user display from attendance metadata on section cards
    - Show only date/time of last attendance update
    - Remove warning messages about unassigned floors/sections
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 8.7, 8.8, 8.9_

  - [ ]* 7.7 Write frontend component tests (Vitest)
    - **Property 9: URL visibility for Owner and Administrator** — verify convention show page includes floor_url and section_url for Owner/Administrator props
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4**
    - Test `nav-convention` renders "Administration" and "Availability" labels
    - Test `role-badge` only renders Owner and Administrator variants
    - Test user dropdown hidden when urlSession is active

- [ ] 8. Update existing tests and seeders
  - [ ] 8.1 Update ConventionTestHelper
    - Replace `ConventionUser` references with `Administrator`
    - Remove FloorUser/SectionUser helper methods and setup
    - Add URL session helper methods for test setup
    - _Requirements: 1.1_

  - [ ] 8.2 Update DemoSeeder
    - Replace `ConventionUser` with `Administrator` in seed data
    - Remove FloorUser and SectionUser seed data
    - Remove floor_user and section_user pivot seeding
    - _Requirements: 1.1, 1.5, 1.6_

  - [ ] 8.3 Update existing feature and property tests
    - Replace all `ConventionUser` references with `Administrator` across test files
    - Remove FloorUser/SectionUser test scenarios
    - Update role-based test assertions to reflect two-role system
    - Update attendance report tests to remove `reported_by` assertions
    - _Requirements: 1.1, 1.5, 1.6, 8.6_

  - [ ] 8.4 Update existing frontend tests
    - Update `use-convention-role` tests for new hook shape
    - Update `user-row` tests to remove FloorUser/SectionUser scenarios
    - Update any test referencing old role names
    - _Requirements: 1.1_

- [ ] 9. Final checkpoint - Ensure all tests pass
  - Run `php artisan test` for backend tests
  - Run `npm run test` for frontend tests
  - Run `npm run types:check` for TypeScript validation
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Run `php artisan wayfinder:generate --with-form` after route changes (task 3.5) before frontend work
- Use `ConventionTestHelper` for shared test setup in all new backend tests
