# Requirements Document

## Introduction

The Convention Management System is a full-stack web application designed to manage conventions with real-time occupancy tracking, attendance reporting, role-based access control, and user invitation flows. The system enables convention organizers to manage floors, sections, and users while providing real-time visibility into section occupancy and attendance across multiple user roles.

## Glossary

- **System**: The Convention Management System web application
- **Convention**: An event with defined dates, location, and organizational structure
- **Floor**: A physical level within a convention venue containing sections
- **Section**: A seating area within a floor with defined capacity and accessibility features
- **Owner**: User role with full administrative privileges for a convention
- **ConventionUser**: User role with read/write access across entire convention
- **FloorUser**: User role with access limited to assigned floors
- **SectionUser**: User role with access limited to assigned sections
- **Occupancy**: Current percentage of seats filled in a section
- **Attendance_Report**: Time-bound data collection period for section attendance
- **Attendance_Period**: Morning or afternoon time segment for attendance tracking
- **Invitation_Link**: Time-limited signed URL for user account activation
- **Email_Confirmation**: Verification status of user email address
- **Responsible_User**: User assigned management duties for specific convention entities
- **PWA**: Progressive Web App installation capability
- **Mailgun**: Email delivery service for system notifications

## Requirements

### Requirement 1: Convention Creation and Validation

**User Story:** As a convention organizer, I want to create conventions with validated date ranges and locations, so that I can prevent scheduling conflicts and ensure data integrity.

#### Acceptance Criteria

1. THE System SHALL require name, city, country, start_date, and end_date for convention creation
2. THE System SHALL accept optional address and other_info fields for conventions
3. WHEN a convention is created with dates overlapping an existing convention in the same city and country, THE System SHALL reject the creation and display an inline validation error
4. WHEN a convention is successfully created, THE System SHALL assign the creator as Owner and ConventionUser
5. WHEN a convention is successfully created, THE System SHALL redirect the user to the Convention Detail Page

### Requirement 2: User Authentication and Session Management

**User Story:** As a user, I want to log in securely with optional persistent sessions, so that I can access the system conveniently while maintaining security.

#### Acceptance Criteria

1. THE System SHALL display a login form with email and password fields on the landing page
2. THE System SHALL provide a "Remember me" checkbox on the login form
3. WHEN the "Remember me" checkbox is selected during login, THE System SHALL create a session cookie valid for 30 days
4. WHEN login credentials are invalid, THE System SHALL display an error message and apply rate limiting
5. WHEN login is successful, THE System SHALL redirect the user to the main window
6. THE System SHALL apply rate limiting to login attempts to prevent brute force attacks

### Requirement 3: User Invitation and Email Confirmation

**User Story:** As a convention manager, I want to invite users via email with secure activation links, so that users can set their passwords and confirm their email addresses.

#### Acceptance Criteria

1. WHEN a new user is created, THE System SHALL send an invitation email via Mailgun containing a time-limited signed URL
2. THE System SHALL generate invitation URLs using Laravel's temporarySignedRoute with expiration
3. WHEN a user clicks a valid invitation link, THE System SHALL display a password creation form
4. WHEN a user sets their password via invitation link, THE System SHALL mark email_confirmed as true
5. WHEN a user's email address is updated, THE System SHALL automatically send a new confirmation email
6. THE System SHALL display email confirmation status with a green checkmark icon for confirmed emails
7. THE System SHALL display email confirmation status with a warning icon for unconfirmed emails
8. WHERE a user has management scope, THE System SHALL display a "Resend invitation" button on user lists
9. THE System SHALL apply rate limiting to invitation resend requests

### Requirement 4: User Email Validation and Deduplication

**User Story:** As a system administrator, I want to enforce email uniqueness and domain restrictions, so that user accounts remain valid and properly connected across conventions.

#### Acceptance Criteria

1. THE System SHALL require globally unique email addresses for all users
2. WHEN an email address is provided during user creation, THE System SHALL reject emails containing "jwpub.org"
3. WHEN a user with an existing email is added to a new convention, THE System SHALL connect the existing user record instead of creating a duplicate
4. THE System SHALL require first_name, last_name, email, and mobile fields for all users

### Requirement 5: Role-Based Access Control

**User Story:** As a convention organizer, I want to assign granular roles to users, so that access is appropriately scoped to their responsibilities.

#### Acceptance Criteria

1. THE System SHALL support four role types: Owner, ConventionUser, FloorUser, and SectionUser
2. THE System SHALL scope roles per convention via the convention_user_roles pivot table
3. THE System SHALL allow users to hold multiple roles simultaneously within a convention
4. WHERE a user has Owner role, THE System SHALL grant all ConventionUser capabilities plus deletion and export privileges
5. WHERE a user has ConventionUser role, THE System SHALL grant read/write access to all floors, sections, and users within the convention
6. WHERE a user has FloorUser role, THE System SHALL limit access to assigned floors and their sections
7. WHERE a user has SectionUser role, THE System SHALL limit access to assigned sections only

### Requirement 6: Floor and Section Management

**User Story:** As a convention manager, I want to organize the venue into floors and sections with detailed attributes, so that I can track capacity and accessibility features.

#### Acceptance Criteria

1. THE System SHALL require name field for floor creation
2. THE System SHALL associate each floor with exactly one convention via convention_id foreign key
3. THE System SHALL require floor_id, name, and number_of_seats for section creation
4. THE System SHALL provide optional elder_friendly and handicap_friendly boolean fields for sections
5. THE System SHALL provide optional information text field for sections
6. THE System SHALL initialize occupancy and available_seats to 0 for new sections
7. THE System SHALL store last_occupancy_updated_by and last_occupancy_updated_at for each section

### Requirement 7: Occupancy Tracking and Updates

**User Story:** As a section manager, I want to update section occupancy in real-time, so that attendees can find available seating.

#### Acceptance Criteria

1. THE System SHALL display number_of_seats prominently on the Section Detail Page
2. THE System SHALL provide an occupancy dropdown with values: 0%, 10%, 25%, 50%, 75%, 100%
3. WHEN occupancy percentage is selected from dropdown, THE System SHALL auto-save the value
4. THE System SHALL provide a "FULL" panic button on the Section Detail Page
5. WHEN the "FULL" button is clicked, THE System SHALL immediately set occupancy to 100% and save
6. THE System SHALL provide a numeric input field for available_seats with a "Send" button
7. WHEN available_seats is submitted, THE System SHALL calculate and save occupancy percentage
8. WHEN occupancy is updated, THE System SHALL record the updating user and timestamp
9. THE System SHALL display last update information in the Section Detail Page footer

### Requirement 8: Daily Occupancy Reset

**User Story:** As a system administrator, I want occupancy data to reset automatically each day, so that tracking remains accurate for each convention day.

#### Acceptance Criteria

1. THE System SHALL execute a Laravel Scheduler task every morning
2. WHEN the daily scheduler task runs, THE System SHALL reset occupancy to 0 for all sections
3. WHEN the daily scheduler task runs, THE System SHALL reset available_seats to number_of_seats for all sections (all seats available)

### Requirement 9: Occupancy Color Coding

**User Story:** As a user, I want visual indicators of occupancy levels, so that I can quickly identify section availability.

#### Acceptance Criteria

1. WHEN occupancy is 0-25%, THE System SHALL display a green color indicator
2. WHEN occupancy is 26-50%, THE System SHALL display a dark green color indicator
3. WHEN occupancy is 51-75%, THE System SHALL display a yellow color indicator
4. WHEN occupancy is 76-90%, THE System SHALL display an orange color indicator
5. WHEN occupancy is 91-100%, THE System SHALL display a red color indicator
6. THE System SHALL apply color coding consistently across all floor and section lists
7. THE System SHALL display color coding as both icon and row background

### Requirement 10: Attendance Reporting Periods

**User Story:** As a convention manager, I want to collect attendance data in morning and afternoon periods, so that I can track attendance patterns throughout each day.

#### Acceptance Criteria

1. THE System SHALL create exactly two attendance periods per day: morning and afternoon
2. THE System SHALL create attendance period records for each day from convention start_date to end_date inclusive
3. THE System SHALL create attendance period records lazily on first access
4. THE System SHALL store attendance value, reported_by user, reported_at timestamp, and locked status for each period
5. WHERE ConventionUser role is assigned, THE System SHALL display a "START ATTENDANCE REPORT" button
6. THE System SHALL limit attendance report activation to maximum 2 times per day
7. WHEN attendance report is active, THE System SHALL display total current attendance
8. WHEN attendance report is active, THE System SHALL display "X of Y sections reported" counter

### Requirement 11: Attendance Report Locking

**User Story:** As a convention manager, I want to lock attendance periods after collection, so that historical data remains immutable.

#### Acceptance Criteria

1. WHERE ConventionUser role is assigned, THE System SHALL provide a "Stop attendance report" button
2. WHEN stop button is clicked and not all sections have reported, THE System SHALL display a confirmation warning
3. WHEN attendance report is stopped, THE System SHALL lock the period permanently
4. WHEN a period is locked, THE System SHALL display that period's attendance data on the Convention page
5. WHEN a section user saves attendance for a period, THE System SHALL restrict updates to only that saving user
6. WHERE ConventionUser locks a period, THE System SHALL override individual section user restrictions

### Requirement 12: Convention Detail Page Access Control

**User Story:** As a user with specific role permissions, I want to see only the data and actions appropriate to my role, so that the interface remains focused and secure.

#### Acceptance Criteria

1. WHERE user has Owner or ConventionUser role, THE System SHALL display all floors, sections, and users for the convention
2. WHERE user has FloorUser role, THE System SHALL display only assigned floors and their sections
3. WHERE user has SectionUser role, THE System SHALL display only assigned sections
4. WHERE user has Owner role, THE System SHALL display convention deletion button with confirmation dialog
5. WHERE user has Owner role, THE System SHALL display export functionality with format dropdown
6. THE System SHALL display floors in a datatable with occupancy color coding
7. WHEN a floor row is clicked, THE System SHALL expand to show sections within that floor

### Requirement 13: Floor Management by Role

**User Story:** As a floor manager, I want to manage sections on my assigned floors, so that I can organize the venue effectively within my scope.

#### Acceptance Criteria

1. WHERE user has FloorUser role, THE System SHALL allow editing floor names for assigned floors only
2. WHERE user has FloorUser role, THE System SHALL prevent adding or deleting floors
3. WHERE user has FloorUser role, THE System SHALL allow adding, editing, and deleting sections on assigned floors
4. WHERE user has FloorUser role, THE System SHALL display sections in a datalist with name, occupancy icon, and available seats
5. WHERE user has FloorUser role, THE System SHALL display "X of Y sections have reported attendance" in footer for current period

### Requirement 14: Section Management by Role

**User Story:** As a section manager, I want to manage users in my assigned sections, so that I can coordinate section-level responsibilities.

#### Acceptance Criteria

1. WHERE user has SectionUser role, THE System SHALL prevent adding, editing, or deleting floors
2. WHERE user has SectionUser role, THE System SHALL allow editing only assigned sections
3. WHERE user has SectionUser role, THE System SHALL allow adding, editing, and deleting users connected to assigned sections only
4. WHEN a SectionUser is responsible for only one section, THE System SHALL load that section's detail view directly in the main window

### Requirement 15: Datatable Access Control

**User Story:** As a user viewing floors, sections, or users, I want to see only the data I have permission to access, so that the interface respects role boundaries.

#### Acceptance Criteria

1. WHERE user has Owner or ConventionUser role, THE System SHALL display all rows in floors, sections, and users datatables
2. WHERE user has FloorUser role, THE System SHALL display only assigned floors in the floors datatable
3. WHERE user has FloorUser role, THE System SHALL hide or disable action buttons for out-of-scope floor rows
4. WHERE user has FloorUser role, THE System SHALL display sections only from assigned floors in the sections datatable
5. WHERE user has SectionUser role, THE System SHALL display floor and section rows as read-only
6. WHERE user has SectionUser role, THE System SHALL display only users connected to assigned sections in the users datatable
7. THE System SHALL hide or disable action buttons for rows the user lacks permission to modify

### Requirement 16: Section Search Functionality

**User Story:** As an attendee, I want to search for available sections with specific accessibility features, so that I can find appropriate seating quickly.

#### Acceptance Criteria

1. THE System SHALL provide a Search page accessible to all authenticated users regardless of role
2. THE System SHALL provide an optional floor filter dropdown on the Search page
3. THE System SHALL provide independent elder_friendly and handicap_friendly filter checkboxes
4. WHEN search is executed, THE System SHALL return only sections where current occupancy is less than 90%
5. THE System SHALL sort search results by occupancy percentage in ascending order
6. THE System SHALL display search results in a paginated list optimized for mobile
7. THE System SHALL display floor name, section name, and occupancy color-coded icon for each result
8. THE System SHALL apply no role-based filtering to search results

### Requirement 17: User Deletion and Disconnection

**User Story:** As a convention manager, I want to remove users from conventions with automatic cleanup, so that user records remain accurate and minimal.

#### Acceptance Criteria

1. WHEN a user is deleted from a convention, THE System SHALL remove all role and pivot records for that convention
2. WHEN a user is disconnected from their last convention, THE System SHALL delete the user record entirely
3. THE System SHALL require confirmation dialog for all user deletion actions

### Requirement 18: Navigation and Responsive Design

**User Story:** As a mobile user, I want a responsive interface optimized for phones and tablets, so that I can manage conventions from any device.

#### Acceptance Criteria

1. WHEN user logs in successfully, THE System SHALL redirect to the main window
2. THE System SHALL provide navigation links to Floors, Sections, Users, and Search pages
3. THE System SHALL scope navigation link visibility based on user role
4. THE System SHALL implement mobile-first responsive design for all pages
5. THE System SHALL support drill-down navigation from Floor to Section

### Requirement 19: Progressive Web App Installation

**User Story:** As a mobile user, I want to install the application on my home screen, so that I can access it like a native app.

#### Acceptance Criteria

1. THE System SHALL provide a Web App Manifest file
2. THE System SHALL implement a basic service worker to support PWA installation
3. THE System SHALL display a PWA installation prompt or banner
4. THE System SHALL provide step-by-step installation instructions accessible via button or info icon
5. THE System SHALL include installation guidance for both iOS and Android platforms

### Requirement 20: Convention Data Export

**User Story:** As a convention owner, I want to export complete convention data in multiple formats, so that I can analyze and archive information externally.

#### Acceptance Criteria

1. WHERE user has Owner role, THE System SHALL display export functionality
2. THE System SHALL provide a format dropdown with options: .docx, .xlsx, Markdown
3. WHEN export is requested, THE System SHALL include all floors, sections with seat counts and occupancy, full attendance history, and users
4. WHEN .xlsx format is selected, THE System SHALL use maatwebsite/excel library for generation
5. WHEN .docx format is selected, THE System SHALL use phpoffice/phpword library for generation
6. WHEN Markdown format is selected, THE System SHALL use plain PHP for generation

### Requirement 21: Input Validation and Security

**User Story:** As a system administrator, I want comprehensive input validation and security controls, so that the application remains secure against common attacks.

#### Acceptance Criteria

1. THE System SHALL validate all inputs server-side using Laravel Form Requests
2. THE System SHALL sanitize all user inputs before processing
3. THE System SHALL apply CSRF protection to all state-changing requests
4. THE System SHALL enforce password criteria: minimum 8 characters, mixed case, number, and symbol
5. THE System SHALL generate invitation links using signed URLs with time expiration
6. THE System SHALL apply rate limiting to login endpoints
7. THE System SHALL apply rate limiting to invitation resend endpoints
8. THE System SHALL require confirmation dialogs for all destructive actions
9. THE System SHALL expose no sensitive data beyond authenticated role requirements

### Requirement 22: Email Configuration and Delivery

**User Story:** As a system administrator, I want reliable email delivery through Mailgun, so that users receive invitations and confirmations promptly.

#### Acceptance Criteria

1. THE System SHALL use Laravel Mailables for all email communications
2. THE System SHALL send emails via Mailgun service
3. THE System SHALL require MAILGUN_DOMAIN configuration in environment
4. THE System SHALL require MAILGUN_SECRET configuration in environment
5. THE System SHALL provide a complete .env.example file with all required email configuration keys
6. THE System SHALL never commit .env file to version control

### Requirement 23: Database Schema and Migrations

**User Story:** As a developer, I want complete database migrations and clear setup instructions, so that I can deploy the application reliably.

#### Acceptance Criteria

1. THE System SHALL provide Laravel migrations for all tables: conventions, floors, sections, users
2. THE System SHALL provide Laravel migrations for all pivot tables: convention_user, floor_user, section_user, convention_user_roles
3. THE System SHALL provide a seeder or clear first-run setup instructions
4. THE System SHALL include a demo Owner account in setup documentation
5. THE System SHALL document all significant architectural decisions

### Requirement 24: Form Validation and Error Display

**User Story:** As a user, I want clear inline validation and error messages, so that I can correct input errors quickly.

#### Acceptance Criteria

1. THE System SHALL validate all forms server-side using Laravel Form Requests
2. WHEN validation fails, THE System SHALL display inline error messages via Inertia
3. THE System SHALL display clear, actionable error messages for all validation failures
4. THE System SHALL preserve user input when validation fails and form is redisplayed

### Requirement 25: Parser and Serializer for Data Export

**User Story:** As a developer, I want robust parsing and serialization for export formats, so that exported data maintains integrity and can be reliably processed.

#### Acceptance Criteria

1. WHEN convention data is exported to .xlsx format, THE System SHALL serialize all convention entities into Excel format
2. WHEN convention data is exported to .docx format, THE System SHALL serialize all convention entities into Word document format
3. WHEN convention data is exported to Markdown format, THE System SHALL serialize all convention entities into valid Markdown syntax
4. FOR ALL export formats, THE System SHALL include floors, sections, attendance history, and users
5. THE System SHALL validate export data structure before serialization to prevent malformed output
