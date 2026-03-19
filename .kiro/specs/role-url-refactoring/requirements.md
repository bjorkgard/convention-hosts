# Requirements Document

## Introduction

This feature refactors the Convention Management System's role-based access control from a four-tier user-based system (Owner, ConventionUser, FloorUser, SectionUser) to a simplified two-tier system (Owner, Administrator) with URL-based anonymous access for floor and section management. The FloorUser and SectionUser roles are replaced by shareable URLs that grant equivalent permissions without requiring user accounts.

## Glossary

- **Convention_System**: The Convention Management System application
- **Owner**: A user who created a convention and has full administrative privileges
- **Administrator**: A user with convention-wide access (replaces ConventionUser role)
- **Floor_URL**: A unique, auto-generated URL that grants anonymous floor management permissions
- **Section_URL**: A unique, auto-generated URL that grants anonymous section management permissions
- **URL_Session**: An anonymous session initiated by accessing Floor_URL or Section_URL
- **Attendance_Report**: A record of attendance data for a section during an attendance period
- **Sidebar**: The main navigation component of the application

## Requirements

### Requirement 1: Role System Simplification

**User Story:** As a convention owner, I want a simplified role system with only Owner and Administrator roles, so that user management is easier to understand and maintain.

#### Acceptance Criteria

1. THE Convention_System SHALL support exactly two user roles: Owner and Administrator
2. WHEN a user creates a convention, THE Convention_System SHALL assign the Owner role to that user
3. THE Owner role SHALL retain all permissions previously held by the Owner role (full administrative privileges, export, delete convention)
4. THE Administrator role SHALL have all permissions previously held by the ConventionUser role (manage floors, sections, users; start/stop attendance reports; lock attendance periods)
5. THE Convention_System SHALL remove the FloorUser role from the role enum, database schema, and all authorization checks
6. THE Convention_System SHALL remove the SectionUser role from the role enum, database schema, and all authorization checks
7. THE Convention_System SHALL remove the floor_user pivot table from the database schema
8. THE Convention_System SHALL remove the section_user pivot table from the database schema
9. WHEN displaying user management options, THE Convention_System SHALL only show Owner and Administrator as available roles

### Requirement 2: URL Generation for Convention Access

**User Story:** As a convention owner, I want unique URLs automatically generated for floor and section access, so that I can share them with volunteers without creating user accounts.

#### Acceptance Criteria

1. WHEN a new convention is created, THE Convention_System SHALL generate a unique Floor_URL token
2. WHEN a new convention is created, THE Convention_System SHALL generate a unique Section_URL token
3. THE Floor_URL token SHALL be a cryptographically secure random string of at least 32 characters
4. THE Section_URL token SHALL be a cryptographically secure random string of at least 32 characters
5. THE Convention_System SHALL store the Floor_URL token and Section_URL token in the conventions table
6. THE Floor_URL and Section_URL tokens SHALL be unique across all conventions
7. FOR ALL existing conventions without URL tokens, THE Convention_System SHALL generate tokens via database migration

### Requirement 3: Floor URL Access Permissions

**User Story:** As a volunteer accessing via Floor_URL, I want to manage all floors and their sections, so that I can help with floor-level operations without needing a user account.

#### Acceptance Criteria

1. WHEN a user accesses the Floor_URL, THE Convention_System SHALL create a URL_Session with floor management permissions
2. THE URL_Session initiated via Floor_URL SHALL grant permission to view all floors in the convention
3. THE URL_Session initiated via Floor_URL SHALL grant permission to view all sections on all floors
4. THE URL_Session initiated via Floor_URL SHALL grant permission to update occupancy for all sections
5. THE URL_Session initiated via Floor_URL SHALL grant permission to report attendance for all sections
6. THE URL_Session initiated via Floor_URL SHALL NOT grant permission to create, edit, or delete floors
7. THE URL_Session initiated via Floor_URL SHALL NOT grant permission to create or delete sections
8. THE URL_Session initiated via Floor_URL SHALL NOT grant permission to access user management
9. THE URL_Session initiated via Floor_URL SHALL NOT grant permission to start or stop attendance reports
10. THE URL_Session initiated via Floor_URL SHALL NOT grant permission to lock attendance periods

### Requirement 4: Section URL Access Permissions

**User Story:** As a volunteer accessing via Section_URL, I want to manage all sections, so that I can help with section-level operations without needing a user account.

#### Acceptance Criteria

1. WHEN a user accesses the Section_URL, THE Convention_System SHALL create a URL_Session with section management permissions
2. THE URL_Session initiated via Section_URL SHALL grant permission to view all sections in the convention
3. THE URL_Session initiated via Section_URL SHALL grant permission to update occupancy for all sections
4. THE URL_Session initiated via Section_URL SHALL grant permission to report attendance for all sections
5. THE URL_Session initiated via Section_URL SHALL NOT grant permission to view or manage floors
6. THE URL_Session initiated via Section_URL SHALL NOT grant permission to create or delete sections
7. THE URL_Session initiated via Section_URL SHALL NOT grant permission to access user management
8. THE URL_Session initiated via Section_URL SHALL NOT grant permission to start or stop attendance reports
9. THE URL_Session initiated via Section_URL SHALL NOT grant permission to lock attendance periods

### Requirement 5: URL Display in Convention View

**User Story:** As a convention owner or administrator, I want to see the Floor_URL and Section_URL prominently displayed, so that I can easily share them with volunteers.

#### Acceptance Criteria

1. WHEN an Owner views the convention page, THE Convention_System SHALL display the complete Floor_URL
2. WHEN an Owner views the convention page, THE Convention_System SHALL display the complete Section_URL
3. WHEN an Administrator views the convention page, THE Convention_System SHALL display the complete Floor_URL
4. WHEN an Administrator views the convention page, THE Convention_System SHALL display the complete Section_URL
5. THE Convention_System SHALL display a clear label identifying each URL (Floor Access URL, Section Access URL)
6. THE Convention_System SHALL display a copy button next to the Floor_URL
7. THE Convention_System SHALL display a copy button next to the Section_URL
8. WHEN the copy button is clicked, THE Convention_System SHALL copy the corresponding URL to the clipboard
9. WHEN the URL is successfully copied, THE Convention_System SHALL display a visual confirmation

### Requirement 6: Floors and Sections List UI Cleanup

**User Story:** As a user viewing floors and sections, I want a cleaner interface without user assignment indicators, so that the UI reflects the new URL-based access model.

#### Acceptance Criteria

1. THE Convention_System SHALL NOT display user assignment icons on floor list items
2. THE Convention_System SHALL NOT display user assignment icons on section list items
3. THE Convention_System SHALL NOT display warning messages about unassigned floors
4. THE Convention_System SHALL NOT display warning messages about unassigned sections

### Requirement 7: Sidebar Navigation Renaming

**User Story:** As a user navigating the application, I want clearer navigation labels, so that I can better understand what each section contains.

#### Acceptance Criteria

1. THE Sidebar SHALL display "Administration" instead of "Floors" for the floors navigation item
2. THE Sidebar SHALL display "Availability" instead of "Search" for the search navigation item

### Requirement 8: Attendance Report User Lock Removal

**User Story:** As any user with section permissions, I want to create or update attendance reports regardless of who started them, so that multiple volunteers can collaborate on attendance collection.

#### Acceptance Criteria

1. THE Convention_System SHALL allow any user with section permissions to create attendance reports
2. THE Convention_System SHALL allow any user with section permissions to update attendance reports
3. THE Convention_System SHALL allow URL_Session users with section permissions to create attendance reports
4. THE Convention_System SHALL allow URL_Session users with section permissions to update attendance reports
5. THE Convention_System SHALL NOT restrict attendance report updates to the original reporter
6. THE Convention_System SHALL remove the reported_by user reference from attendance report records
7. WHEN displaying attendance report metadata, THE Convention_System SHALL show only the date and time of the last update
8. WHEN displaying attendance report metadata, THE Convention_System SHALL NOT show the user name who made the update
9. THE Convention_System SHALL display the date and time of the most recent attendance report update on the section view

### Requirement 9: URL Session UI Restrictions

**User Story:** As a volunteer accessing via URL, I want a simplified interface without user account options, so that I can focus on my assigned tasks.

#### Acceptance Criteria

1. WHILE a URL_Session is active, THE Sidebar SHALL NOT display the user dropdown menu
2. WHILE a URL_Session is active, THE Convention_System SHALL NOT display user profile options
3. WHILE a URL_Session is active, THE Convention_System SHALL NOT display logout options in the sidebar
4. WHEN a URL_Session is initiated via Floor_URL, THE Convention_System SHALL set the appearance theme to Apple
5. WHEN a URL_Session is initiated via Section_URL, THE Convention_System SHALL set the appearance theme to Apple

### Requirement 10: Database Migration for Existing Data

**User Story:** As a system administrator, I want existing data to be properly migrated, so that the system continues to function after the role refactoring.

#### Acceptance Criteria

1. WHEN the migration runs, THE Convention_System SHALL convert all ConventionUser roles to Administrator roles
2. WHEN the migration runs, THE Convention_System SHALL remove all FloorUser role assignments
3. WHEN the migration runs, THE Convention_System SHALL remove all SectionUser role assignments
4. WHEN the migration runs, THE Convention_System SHALL drop the floor_user pivot table
5. WHEN the migration runs, THE Convention_System SHALL drop the section_user pivot table
6. WHEN the migration runs, THE Convention_System SHALL generate Floor_URL and Section_URL tokens for all existing conventions
7. WHEN the migration runs, THE Convention_System SHALL remove the reported_by column from attendance_reports table
8. THE migration SHALL be reversible to restore the previous role structure
