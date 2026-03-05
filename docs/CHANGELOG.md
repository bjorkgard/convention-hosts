# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-03-05

### Added

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
