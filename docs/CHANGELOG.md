# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-03-06

### Other

- docs: add comprehensive documentation and update environment configuration (, |Nathanael Björkgård|||2026-03-06)
- add Kiro hooks for auto-linting and fix import ordering (, |Nathanael Björkgård|||2026-03-06)


## [Unreleased] - 2026-03-06

### Other

- upgrade maatwebsite/excel to 4.x-dev and phpspreadsheet to 5.5 (, |Nathanael Björkgård|||2026-03-06)


## [Unreleased] - 2026-03-06

### Added

- add Vitest configuration for frontend component testing (2026-03-06)
  - `vitest.config.ts` with jsdom environment, React plugin, and `@` path alias
  - Test setup file at `resources/js/test/setup.ts` importing jest-dom matchers
  - `npm test` script runs `vitest run` for single-pass execution
  - Dev dependencies: vitest, @testing-library/react, @testing-library/jest-dom, @testing-library/user-event, fast-check, jsdom

### Changed

- extend Pest configuration to support Unit tests with `TestCase` and `RefreshDatabase` (2026-03-06)
  - Unit tests now have full Laravel application access and fresh database per test
  - Aligns Unit directory with existing Feature and Property test configuration
- improve ConventionFactory with `withOwner()` state and tighter date ranges (2026-03-06)
  - Add `withOwner(?User $owner)` factory state that attaches Owner + ConventionUser roles
  - Narrow default date range to +1 week to +6 months start, max +2 weeks duration
  - Simplifies test setup for convention scenarios requiring an authenticated owner

### Added

- add `ConventionTestHelper` utility class for test setup (2026-03-06)
  - `createConventionWithStructure()` builds full convention hierarchy (floors, sections, optional owner)
  - `createUserWithRole()` creates users with role and pivot attachments (FloorUser/SectionUser scoping)
  - `attachUserToConvention()` handles convention_user and convention_user_roles pivot inserts
  - `createAuthenticatedUser()` shorthand for quick test scenario setup
- add CSRF protection feature tests (`CsrfProtectionTest.php`) (2026-03-06)
  - Verifies ValidateCsrfToken middleware is in web middleware group
  - Tests rejection of POST/PUT/PATCH/DELETE requests without CSRF token (419 status)
  - Confirms valid CSRF token allows state-changing requests
  - Validates all application state-changing routes use web middleware
  - Asserts no CSRF token exclusions are configured
- add PWA icon generation script (`generate-icons.php`) using PHP GD (2026-03-06)
  - Generates blue (#3b82f6) PNG icons with "CM" text at 8 sizes (72–512px)
  - Outputs to `public/icons/` for Web App Manifest
  - Uses TTF fonts when available, falls back to GD built-in fonts
  - Temporary script — delete after running

## [Unreleased] - 2026-03-05

### Added

- add NavConvention sidebar component with role-based convention navigation (2026-03-05)
  - Context-aware sidebar section showing Floors, Sections, Users, Search links
  - Role-based visibility: Floors and Users hidden from SectionUser role
  - Wayfinder type-safe URL generation for all navigation links
  - Active state highlighting via useCurrentUrl hook
  - Integrated into AppSidebar below main navigation
- add conventions index page component (2026-03-05)
  - Inertia page listing user's conventions with ConventionCard grid
  - Empty state with prompt to create first convention
  - "Create Convention" button using Wayfinder type-safe routing
  - Responsive grid layout (1/2/3 columns)
- add convention management UI components and tests (, |Nathanael Björkgård|||2026-03-05)

### Other

- docs(structure): update project structure documentation with implemented features (, |Nathanael Björkgård|||2026-03-05)


## [Unreleased] - 2026-03-05

### Added

- add useConventionRole React hook for frontend role-based access control (2026-03-05)
  - Reads userRoles, userFloorIds, userSectionIds from Inertia page props
  - Exposes isOwner, isConventionUser, isFloorUser, isSectionUser booleans
  - Provides hasFloorAccess() and hasSectionAccess() scope helpers
  - Memoized via useMemo for render performance
- implement daily occupancy reset command and scheduling (, |Nathanael Björkgård|||2026-03-05)
- add TypeScript interfaces for convention data models (, |Nathanael Björkgård|||2026-03-05)


## [Unreleased] - 2026-03-05

### Added

- add property test for invitation email delivery (2026-03-05)
  - Validates signed URL generation and Mailgun delivery for user invitations
  - 50-iteration randomized test covering Requirements 3.1, 3.2
  - Asserts exactly one email sent per invitation with correct recipient and signed URL
- implement ConventionController with full CRUD, role-scoped data loading, and export (2026-03-05)
  - index/create/store for convention listing and creation
  - show with role-scoped floors, sections, attendance periods, and users
  - update/destroy with policy-based authorization
  - export with format selection and auto-delete after download
- implement middleware and policies for role-based access control (, |Nathanael Björkgård|||2026-03-05)


## [Unreleased] - 2026-03-05

### Added

- implement EnsureConventionAccess middleware for role-based access control (2026-03-05)
  - Verifies authenticated users have at least one role for requested convention
  - Gracefully skips when no convention parameter in route
  - Returns 403 Forbidden with clear error message for unauthorized access
  - Uses Eloquent relationships for efficient access verification
- implement ConventionExport with multi-sheet Excel architecture (2026-03-05)
  - Four-sheet workbook structure: Convention, Floors & Sections, Attendance History, Users
  - Eager loading optimization to prevent N+1 queries during export
  - Implements WithMultipleSheets interface from maatwebsite/excel
  - Comprehensive data export including all relationships and attendance records
- implement CreateConventionAction with automatic role assignment (2026-03-05)
  - Creates conventions with creator assigned as Owner and ConventionUser
  - Transaction-safe operation with automatic rollback on failure
  - Lazy attendance period creation for optimized setup
- implement validation classes and update user model (, |Nathanael Björkgård|||2026-03-05)


## [Unreleased] - 2026-03-05

### Other

- docs(steering): add comprehensive convention management system documentation (, |Nathanael Björkgård|||2026-03-05)


## [Unreleased] - 2026-03-05

### Added

- add Convention model with relationships and role management methods (2026-03-05)
- add database migrations and documentation for convention management system (, |Nathanael Björkgård|||2026-03-05)

### Other

- add convention management system specification (, |Nathanael Björkgård|||2026-03-05)


## [Unreleased] - 2026-03-05

### Added

- add comprehensive skill documentation for Laravel and React development (, |Nathanael Björkgård|||2026-03-05)


## [Unreleased] - 2026-03-05

### Other

- add changelog update hook for pre-push automation (Nathanael Björkgård, 2026-03-05)
- add documentation sync hook and docs directory (Nathanael Björkgård, 2026-03-05)
- add ui-ux-pro-max design system steering (Nathanael Björkgård, 2026-03-05)
- add project steering documentation (Nathanael Björkgård, 2026-03-05)
- Configure Boost post-update script (Nathanael Björkgård, 2026-03-05)
- Install Laravel Boost (Nathanael Björkgård, 2026-03-05)
- Install Pest (Nathanael Björkgård, 2026-03-05)
- Set up a fresh Laravel app (Nathanael Björkgård, 2026-03-05)
