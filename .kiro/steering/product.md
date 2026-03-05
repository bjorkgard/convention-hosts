# Product Overview

This is a Convention Management System built with Laravel and React that enables convention organizers to manage multi-day events with real-time occupancy tracking, attendance reporting, and role-based access control.

## Core Features

### Convention Management
- Create and manage conventions with date validation and conflict detection
- Hierarchical venue organization (Convention → Floor → Section)
- Multi-format data export (.xlsx, .docx, Markdown)

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
- Four-tier role-based access control:
  - Owner: Full administrative privileges
  - ConventionUser: Convention-wide access
  - FloorUser: Floor-scoped access
  - SectionUser: Section-scoped access
- User profile management
- Two-factor authentication (2FA) with recovery codes
- Password management
- Appearance/theme customization

### Search & Accessibility
- Mobile-optimized search for available sections
- Accessibility filters (elder-friendly, handicap-friendly)
- Progressive Web App (PWA) installation support

## Technology Approach

The application uses Inertia.js to bridge Laravel (backend) and React (frontend), enabling a modern SPA experience while maintaining traditional server-side routing. Laravel Fortify handles authentication flows, and Laravel Wayfinder provides type-safe routing between backend and frontend. The system is optimized for mobile-first usage with PWA capabilities for on-site convention management.
