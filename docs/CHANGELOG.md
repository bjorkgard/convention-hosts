# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-03-06

### Removed

- remove self-registration sign-up link from login page; users now join exclusively via invitation or guest convention creation (2026-03-06)

### Other

- add Laravel Cloud deployment step to release workflow (, |Nathanael Björkgård|||2026-03-06)


## [Unreleased] - 2026-03-06

### Added

- add mobile number field to guest convention creation (, |Nathanael Björkgård|||2026-03-06)


## [Unreleased] - 2026-03-06

### Added

- update floor row collapsible icon and improve import organization (, |Nathanael Björkgård|||2026-03-06)


## [Unreleased]

- add occupancy help text to section detail page explaining controls and daily reset (2026-03-06)
- replace OccupancyIndicator with OccupancyGauge in section detail page for consistent semi-circle gauge display (2026-03-06)
- eager-load assigned users on floors and sections in convention show endpoint for user display (2026-03-06)
- replace OccupancyIndicator with OccupancyGauge in search results for consistent semi-circle gauge display (2026-03-06)
- initialize available_seats to number_of_seats on section creation so new sections start fully available (2026-03-06)
- enhance OccupancyGauge with tooltip showing occupancy level label and human-readable descriptions (2026-03-06)
- add OccupancyGauge SVG semi-circle component for visual occupancy display (2026-03-06)
- add FloorRow collapsible component with inline sections, role-based actions, occupancy indicators, and assigned user tooltips (2026-03-06)
- eager-load assigned users on floors index endpoint for floor-level user display (2026-03-06)

## [v0.1.0] - 2026-03-06

- docs(steering): update conventions, product, structure, and tech documentation (Nathanael Björkgård, 2026-03-06)
- add section frontend property tests for role-based UI behavior (Nathanael Björkgård, 2026-03-06)
- wire section CRUD into FloorsIndex page (Nathanael Björkgård, 2026-03-06)
- test(security): enhance test coverage and fix code style issues (Nathanael Björkgård, 2026-03-06)
- add sections index page component (Nathanael Björkgård, 2026-03-06)
- docs: add comprehensive documentation and update environment configuration (Nathanael Björkgård, 2026-03-06)
- add Kiro hooks for auto-linting and fix import ordering (Nathanael Björkgård, 2026-03-06)
- upgrade maatwebsite/excel to 4.x-dev and phpspreadsheet to 5.5 (Nathanael Björkgård, 2026-03-06)
- add Vitest configuration for frontend component testing (Nathanael Björkgård, 2026-03-06)
- extend Pest configuration to support Unit tests with TestCase and RefreshDatabase (Nathanael Björkgård, 2026-03-06)
- improve ConventionFactory with withOwner() state and tighter date ranges (Nathanael Björkgård, 2026-03-06)
- add ConventionTestHelper utility class for test setup (Nathanael Björkgård, 2026-03-06)
- add CSRF protection feature tests (Nathanael Björkgård, 2026-03-06)
- add PWA icon generation script using PHP GD (Nathanael Björkgård, 2026-03-06)
- add NavConvention sidebar component with role-based convention navigation (Nathanael Björkgård, 2026-03-05)
- add conventions index page component (Nathanael Björkgård, 2026-03-05)
- add convention management UI components and tests (Nathanael Björkgård, 2026-03-05)
- add useConventionRole React hook for frontend role-based access control (Nathanael Björkgård, 2026-03-05)
- implement daily occupancy reset command and scheduling (Nathanael Björkgård, 2026-03-05)
- add TypeScript interfaces for convention data models (Nathanael Björkgård, 2026-03-05)
- docs(structure): update project structure documentation with implemented features (Nathanael Björkgård, 2026-03-05)
- add property test for invitation email delivery (Nathanael Björkgård, 2026-03-05)
- implement ConventionController with full CRUD, role-scoped data loading, and export (Nathanael Björkgård, 2026-03-05)
- implement middleware and policies for role-based access control (Nathanael Björkgård, 2026-03-05)
- implement EnsureConventionAccess middleware for role-based access control (Nathanael Björkgård, 2026-03-05)
- implement ConventionExport with multi-sheet Excel architecture (Nathanael Björkgård, 2026-03-05)
- implement CreateConventionAction with automatic role assignment (Nathanael Björkgård, 2026-03-05)
- implement validation classes and update user model (Nathanael Björkgård, 2026-03-05)
- docs(steering): add comprehensive convention management system documentation (Nathanael Björkgård, 2026-03-05)
- add Convention model with relationships and role management methods (Nathanael Björkgård, 2026-03-05)
- add database migrations for convention management system (Nathanael Björkgård, 2026-03-05)
- add convention management system specification (Nathanael Björkgård, 2026-03-05)
- add comprehensive skill documentation for Laravel and React development (Nathanael Björkgård, 2026-03-05)
- add changelog update hook for pre-push automation (Nathanael Björkgård, 2026-03-05)
- add documentation sync hook and docs directory (Nathanael Björkgård, 2026-03-05)
- add ui-ux-pro-max design system steering (Nathanael Björkgård, 2026-03-05)
- add project steering documentation (Nathanael Björkgård, 2026-03-05)
- Configure Boost post-update script (Nathanael Björkgård, 2026-03-05)
- Install Laravel Boost (Nathanael Björkgård, 2026-03-05)
- Install Pest (Nathanael Björkgård, 2026-03-05)
- Set up a fresh Laravel app (Nathanael Björkgård, 2026-03-05)
