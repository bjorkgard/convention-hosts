# Product Overview

This is a Convention Management System built with Laravel and React that enables convention organizers to manage multi-day events with real-time occupancy tracking, attendance reporting, and role-based access control.

## Core Features

### Convention Management
- Create and manage conventions with date validation and conflict detection
- Guest convention creation (unauthenticated users can create a convention and become its owner)
- Convention editing and deletion (owner-only for delete)
- Hierarchical venue organization (Convention → Floor → Section)
- Multi-format data export (.xlsx, .docx, Markdown)
- URL-based anonymous access via shareable section URL token

### Section CRUD Management
- Create sections via modal dialog from the floors index page
- Edit section details (name, seats, accessibility flags, information)
- Delete sections with authorization checks
- Floor selector in create mode, read-only floor display in edit mode
- Role-based authorization: Owner and Administrator can manage all sections

### Occupancy Tracking
- Real-time section occupancy tracking with visual indicators
- Color-coded occupancy levels (0-100%)
- Daily automatic occupancy reset
- Available seats calculation

### Attendance Reporting
- Time-bound attendance reporting with morning/afternoon periods
- Section-by-section attendance collection
- Attendance period locking for historical data integrity
- Comprehensive attendance history

### User Management & Authentication
- User authentication (login with "remember me" option)
- Secure user invitation flow with email confirmation
- User editing and role reassignment
- User removal from convention (deletes user record if no remaining conventions)
- Two-tier authenticated role system supplemented by URL-based anonymous access:
  - Owner: Full administrative privileges
  - Administrator: Convention-wide management access
  - Section URL session: Anonymous access via shareable URL (view floors/sections, update occupancy, report attendance)
- User profile management
- Two-factor authentication (2FA) with recovery codes
- Password management
- Appearance/theme customization

### Search & Accessibility
- Mobile-optimized search for available sections
- Accessibility filters (elder-friendly, handicap-friendly, hearing loop)
- Progressive Web App (PWA) installation support

### Internationalization (i18n)
- Multi-language support with locale auto-discovery from lang/ directory
- User and convention locale preferences
- Laravel-to-i18next translation format conversion
- Locale selector component in UI
- SetLocale middleware for request-level locale resolution

### Consent Management
- Cookie consent banner for anonymous users
- Authenticated consent prompt for logged-in users
- Consent state tracking (accepted, declined, undecided)
- Consent versioning with timestamps

### Version Checking
- GitHub release version checking via VersionController API
- Cached latest release info (5-minute TTL)
- Update notification modal when new version available
- Version badge display in UI

### Automated Maintenance
- Daily occupancy reset (6:00 AM)
- Cleanup of unconfirmed guest conventions after 7 days (3:00 AM)

### Security
- Security event logging (failed logins, authorization failures, invalid signed URLs, rate limit violations)
- Secure HTTP headers
- Input sanitization on all form requests
- CSRF protection
- Rate limiting on login and invitation resend

## Technology Approach

The application uses Inertia.js to bridge Laravel (backend) and React (frontend), enabling a modern SPA experience while maintaining traditional server-side routing. Laravel Fortify handles authentication flows, and Laravel Wayfinder provides type-safe routing between backend and frontend. The system is optimized for mobile-first usage with PWA capabilities for on-site convention management.
