# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- include attendance count and period in success flash message
- pass myReport prop to section show page
- show attendance report status and pre-fill in section form

### Other

- add attendance report status feedback design
- add attendance report status implementation plan
- add missing myReport null branch for no active period

## [v0.5.2] - 2026-03-09

### Added

- install sonner and add Toaster wrapper component
- share flash success/error messages via Inertia middleware
- add flash success messages to occupancy update endpoints
- add useFlashToast hook to fire Sonner toasts from Inertia flash props
- mount Sonner Toaster in app layout
- show Sonner toasts for occupancy and attendance actions on section page
- show Sonner toasts for attendance start/stop on convention page
- implement Sonner toast feedback for occupancy and attendance actions

### Fixed

- remove unused period_id validation rule from ReportAttendanceRequest
- use flash error for service exceptions in AttendanceController, align tests
- add .worktrees directory to ESLint config and reorder imports in app-sidebar-layout

## [v0.5.1] - 2026-03-09

## [v0.5.0] - 2026-03-09

### Added

- implement theme selection feature with multiple color themes
- Add V3 Security Overhaul, Swarm Coordination, and Verification & Quality Assurance skills
- add Apple theme support with iOS design guidelines
- add Android M3 color variables to app.css
- add Android M3 component overrides to app.css
- add Android theme with auto-detection for Android devices
- add use-cookie-consent hook with versioned consent storage
- add CookieConsentBanner component
- gate preference cookie writes behind consent

### Fixed

- change floor_id and convention_id types from number to string in interfaces
- update convention ID type to string and adjust date formatting to Swedish locale
- remove unused favicon links from app layout
- reorder imports for consistency and clarity across helper files
- fix Android theme section comment and hue consistency in dark mode
- add Android background-color to inline style block in app.blade.php
- fix flaky occupancy test by mirroring action's snap-to-dropdown logic
- use non-generic importOriginal in vi.mock

### Other

- Merge pull request #1 from bjorkgard/apple-theme
- add Android Material Design 3 theme design doc and implementation plan
- Merge pull request #2 from bjorkgard/feature/android-m3-theme
- add cookie consent banner design
- add cookie consent banner implementation plan
- ignore .worktrees directory

## [v0.4.4] - 2026-03-08

## [v0.4.3] - 2026-03-08

### Added

- add Mailgun transport configuration to mailers
- update app icons and manifest for improved branding and PWA support
- replace img tags with AppLogoIcon component for consistency and improved performance

### Other

- Add BM25 search engine for UI/UX style guides with design system generation and persistence options
- update documentation for architecture, authentication, development, installation, and testing guides

## [v0.4.2] - 2026-03-08

### Added

- Add Mailgun configuration to services

### Changed

- Update hooks for changelog management before commit and push
- Standardize date formatting in formatDateRange function

### Fixed

- Change id type from number to string in User, Convention, Floor, Section, and AttendancePeriod interfaces
- Implement confirmation page and redirect after guest convention creation

## [v0.4.1] - 2026-03-07

### Added

- Auto-show PWA install dialog on first mobile visit with localStorage dismissal tracking
- Enhance update reload to clear service workers, browser caches, and use cache-bust navigation for reliable asset refresh
- Add tooltip descriptions to role badges explaining each role's access level
- Enhance conventions index page with description text, tooltip on create button, and styled empty state
- Make resend invitation button visible to all convention users, not just managers (disabled when email already confirmed)
- Enhance floors index page with description text and tooltips on Add Floor/Add Section buttons
- Add tooltips to floor row edit and delete buttons for clearer action descriptions
- Add tooltip to available seats Send button explaining occupancy update action
- Enhance UI with typography, branding, and visual polish
- Enhance component styling with dark mode support and visual refinements

### Changed

- Snap available-seats occupancy calculation to closest dropdown option (0, 10, 25, 50, 75, 100) instead of raw rounded percentage

### Fixed

- Improve GitHub API error handling and caching logic
- Update profile and email rules to accept string and UUID types

### Other

- Streamline CLAUDE.md by removing outdated guidelines and enhancing project overview

## [v0.4.0] - 2026-03-07

## [v0.3.0] - 2026-03-06

### Fixed

- Handle 404 responses from GitHub API gracefully

## [v0.2.2] - 2026-03-06

### Fixed

- Handle 404 responses from GitHub API gracefully

## [v0.2.1] - 2026-03-06

### Other

- Remove self-registration endpoint test
- Remove self-registration feature tests

## [v0.2.0] - 2026-03-06

### Added

- Add automatic cleanup of unconfirmed conventions after 7 days
- Add in-app update notification modal that alerts users when a new version is available, with release notes and one-click reload
- Add email verification flow for new guest convention users: new users now receive a verification email with a signed URL to set their password before gaining access; existing users retain auto-login behavior
- Add Laravel Cloud deployment step to release workflow
- Add mobile number field to guest convention creation
- Add occupancy help text to section detail page explaining controls and daily reset
- Replace OccupancyIndicator with OccupancyGauge in section detail page for consistent semi-circle gauge display
- Replace OccupancyIndicator with OccupancyGauge in search results for consistent semi-circle gauge display
- Initialize available_seats to number_of_seats on section creation so new sections start fully available
- Enhance OccupancyGauge with tooltip showing occupancy level label and human-readable descriptions
- Add OccupancyGauge SVG semi-circle component for visual occupancy display
- Add FloorRow collapsible component with inline sections, role-based actions, occupancy indicators, and assigned user tooltips
- Remove self-registration sign-up link from login page; users now join exclusively via invitation or guest convention creation

### Changed

- Update floor row collapsible icon and improve import organization
- Revert daily occupancy reset to set available_seats to number_of_seats
- Eager-load assigned users on floors and sections in convention show endpoint for user display
- Eager-load assigned users on floors index endpoint for floor-level user display

### Other

- Reduce property test iteration counts for faster feedback
- Clarify conventions, flows, validation, and security details in docs

## [v0.1.0] - 2026-03-06

### Added

- Add section frontend property tests for role-based UI behavior
- Wire section CRUD into FloorsIndex page
- Add sections index page component
- Add Kiro hooks for auto-linting and fix import ordering
- Upgrade maatwebsite/excel to 4.x-dev and phpspreadsheet to 5.5
- Add Vitest configuration for frontend component testing
- Extend Pest configuration to support Unit tests with TestCase and RefreshDatabase
- Improve ConventionFactory with withOwner() state and tighter date ranges
- Add ConventionTestHelper utility class for test setup
- Add CSRF protection feature tests
- Add PWA icon generation script using PHP GD
- Add NavConvention sidebar component with role-based convention navigation
- Add conventions index page component
- Add convention management UI components and tests
- Add useConventionRole React hook for frontend role-based access control
- Implement daily occupancy reset command and scheduling
- Add TypeScript interfaces for convention data models
- Add property test for invitation email delivery
- Implement ConventionController with full CRUD, role-scoped data loading, and export
- Implement middleware and policies for role-based access control
- Implement EnsureConventionAccess middleware for role-based access control
- Implement ConventionExport with multi-sheet Excel architecture
- Implement CreateConventionAction with automatic role assignment
- Implement validation classes and update user model
- Add Convention model with relationships and role management methods
- Add database migrations for convention management system
- Add convention management system specification
- Add comprehensive skill documentation for Laravel and React development
- Add changelog update hook for pre-push automation
- Add documentation sync hook and docs directory
- Add ui-ux-pro-max design system steering
- Add project steering documentation
- Configure Boost post-update script
- Install Laravel Pest
- Set up a fresh Laravel app

### Other

- Add comprehensive documentation and update environment configuration
- Update project structure documentation with implemented features
- Add comprehensive convention management system documentation
- Update conventions, product, structure, and tech documentation
- Update conventions, flows, validation, and security details in docs
- Enhance test coverage and fix code style issues
