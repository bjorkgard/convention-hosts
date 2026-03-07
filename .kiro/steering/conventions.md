# Convention Management System

## Overview

The Convention Management System is the core feature of this application, enabling organizers to manage multi-day events with hierarchical venue organization, real-time occupancy tracking, and attendance reporting.

## Data Model Hierarchy

```
Convention
  ├─ Floors
  │   └─ Sections
  │       ├─ Occupancy tracking
  │       └─ Attendance reports
  ├─ Users (with roles)
  └─ Attendance Periods
```

## Key Concepts

### Conventions
- Represent physical events with defined dates and locations
- Have start_date and end_date
- Located in a specific city and country
- Can have optional address and other_info fields
- Automatically validate against overlapping conventions in the same location
- Can be created by authenticated users or guests (guest convention creation flow)
- Can be updated by users with convention access (Owner or ConventionUser)
- Can be deleted by Owner role only

### Floors
- Physical levels within a convention venue
- Belong to exactly one convention
- Contain multiple sections
- Can be assigned to FloorUser roles for scoped management
- Can be created by Owner and ConventionUser (FloorUser cannot add floors)
- Can be edited by Owner, ConventionUser, and assigned FloorUser
- Can be deleted by Owner and ConventionUser only

### Sections
- Seating areas within a floor
- Have defined capacity (number_of_seats)
- Track real-time occupancy (0-100%)
- Track available_seats
- Support accessibility features:
  - elder_friendly (boolean)
  - handicap_friendly (boolean)
- Optional information text field
- Store last occupancy update metadata (user, timestamp)
- Can be assigned to SectionUser roles for scoped management

### Occupancy Tracking
- Occupancy percentage: 0%, 10%, 25%, 50%, 75%, 100%
- Available seats: Numeric input that auto-calculates occupancy
- "FULL" panic button: Instantly sets occupancy to 100%
- Color-coded visual indicators:
  - 0-25%: Green
  - 26-50%: Dark green
  - 51-75%: Yellow
  - 76-90%: Orange
  - 91-100%: Red
- Daily automatic reset at 6:00 AM

### Attendance Reporting
- Time-bound data collection periods
- Two periods per day: morning and afternoon
- Period determination based on current time (< 12:00 = morning)
- Maximum 2 reports per day per convention
- Sections report attendance individually
- Periods can be locked to prevent further updates
- Locked periods become immutable historical records
- Business logic encapsulated in AttendanceReportService

## Section CRUD Management

### Create Section
- Accessed via modal dialog from the floors index page
- Floor selector dropdown (auto-selects when only one floor exists)
- Required fields: name, number_of_seats
- Optional fields: elder_friendly, handicap_friendly, information
- floor_id can come from route parameter or request body (for FloorsIndex modal)
- Redirects to floors index after creation

### Edit Section
- Same modal dialog in edit mode
- Floor displayed as read-only (cannot change floor assignment)
- Pre-populates all current values
- Redirects to floors index after update

### Delete Section
- Authorized via SectionPolicy
- Cascading cleanup handled by database
- Redirects to floors index after deletion

### Section CRUD Authorization
- Owner: Can create, update, and delete all sections
- ConventionUser: Can create, update, and delete all sections
- FloorUser: Can create, update, and delete sections on assigned floors only
- SectionUser: Can update assigned sections only (cannot create or delete)

## Role-Based Access Control

### Owner
- Full administrative privileges
- Can delete conventions
- Can export convention data
- Has all ConventionUser capabilities
- Can override all restrictions

### ConventionUser
- Convention-wide read/write access
- Can manage all floors, sections, and users
- Can start/stop attendance reports
- Can lock attendance periods
- Cannot delete conventions or export data

### FloorUser
- Access limited to assigned floors
- Can view/edit assigned floors
- Can create, update, and delete sections on assigned floors
- Can report attendance for sections on assigned floors
- Cannot add or delete floors

### SectionUser
- Access limited to assigned sections
- Can view/edit assigned sections only
- Can update occupancy for assigned sections
- Can report attendance for assigned sections
- Cannot create or delete sections
- Cannot manage floors or add sections
- If responsible for only one section, automatically loads that section's detail view

## Guest Convention Creation Flow

1. Unauthenticated user submits convention creation form from welcome page
2. System validates user fields (first_name, last_name, email, mobile) and convention fields
3. If email exists: Uses existing user account, creates convention, logs user in, redirects to convention show page
4. If new user:
   a. Creates user with random password and email_confirmed=false
   b. Creates convention via CreateConventionAction (user becomes Owner + ConventionUser)
   c. Generates signed verification URL (24-hour expiration)
   d. Sends GuestConventionVerification email
   e. Renders confirmation page (user is NOT logged in)
   f. User must click verification link, set password, then gets logged in and redirected to convention

## User Invitation Flow

1. Convention manager creates user with email and roles
2. System checks if email already exists
3. If exists: Attach existing user to convention
4. If new: Create user record without password
5. Generate signed URL with 24-hour expiration
6. Send invitation email via UserInvitation mailable
7. User clicks invitation link
8. System verifies signature and expiration
9. User sets password
10. Email is automatically confirmed
11. User is redirected to login page

## User Removal Flow

1. Convention manager deletes user from convention
2. System removes all roles, floor assignments, and section assignments for that convention
3. System detaches user from convention_user pivot
4. If user has no remaining conventions, the user record is deleted entirely

## Occupancy Update Methods

### Dropdown Selection
- Select from predefined percentages: 0%, 10%, 25%, 50%, 75%, 100%
- Auto-saves on selection
- Updates occupancy field directly

### Available Seats Input
- Enter numeric value for available seats
- Click "Send" button to submit
- System calculates occupancy: `100 - ((available_seats / number_of_seats) * 100)`
- Updates both occupancy and available_seats fields

### FULL Button
- Single-click panic button
- Immediately sets occupancy to 100%
- Auto-saves without confirmation

## Attendance Reporting Workflow

### Starting a Report
1. ConventionUser clicks "START ATTENDANCE REPORT"
2. System checks if max 2 reports/day limit reached
3. System determines current period (morning/afternoon)
4. System creates AttendancePeriod record (locked=false)
5. Active report banner displays with counter

### Collecting Attendance
1. Section managers navigate to their sections
2. Enter attendance count in numeric input
3. System creates/updates AttendanceReport record
4. Records reported_by user and reported_at timestamp
5. Counter updates: "X of Y sections reported"

### Stopping a Report
1. ConventionUser clicks "Stop attendance report"
2. If not all sections reported, system shows confirmation warning
3. On confirmation, system sets period.locked = true
4. Period becomes immutable
5. Locked period data displays on Convention page

### Update Restrictions
- Before lock: Only the user who reported can update their section's attendance
- After lock: No updates allowed (period is immutable)
- ConventionUser override: Can lock period even if incomplete

## Search Functionality

### Available to All Users
- No role-based filtering applied
- Accessible to any authenticated user with convention access

### Search Filters
- Floor: Optional dropdown to filter by specific floor
- Elder-friendly: Checkbox to filter sections with elder_friendly=true
- Handicap-friendly: Checkbox to filter sections with handicap_friendly=true

### Search Results
- Only shows sections with occupancy < 90%
- Sorted by occupancy percentage (ascending)
- Displays: floor name, section name, color-coded occupancy gauge
- Paginated (20 per page) for mobile optimization
- Click to navigate to section detail

## Data Export

### Available to Owner Role Only
- Export complete convention data
- Three format options:
  - .xlsx (Excel) - Uses maatwebsite/excel
  - .docx (Word) - Uses phpoffice/phpword
  - Markdown - Plain PHP generation

### Exported Data Includes
- Convention details (name, dates, location)
- All floors with sections
- Section details (capacity, occupancy, accessibility)
- Full attendance history (all periods and reports)
- All users with roles

## Validation Rules

### Convention Creation
- Required: name, city, country, start_date (after_or_equal:today), end_date (after_or_equal:start_date)
- Optional: address, other_info
- Custom rule: No overlapping conventions in same city/country
- SanitizesInput trait applied (other_info treated as rich text)

### Convention Update
- Required: name, city, country, start_date, end_date (after_or_equal:start_date)
- Optional: address, other_info
- Note: start_date does NOT have after_or_equal:today constraint (allows editing past conventions)
- Same overlap validation (excludes current convention from check)
- Uses UpdateConventionRequest with SanitizesInput trait

### Guest Convention Creation
- User fields required: first_name, last_name, email, mobile
- Convention fields required: name, city, country, start_date (after_or_equal:today), end_date (after_or_equal:start_date)
- Convention fields optional: address, other_info
- Same overlap validation as regular creation

### Section Creation
- Required: name, number_of_seats
- Optional: floor_id (sometimes required, from route or request body), elder_friendly, handicap_friendly, information
- number_of_seats must be positive integer
- occupancy defaults to 0
- available_seats defaults to number_of_seats (new sections start fully available)

### Section Update
- Required: name, number_of_seats
- Optional: elder_friendly, handicap_friendly, information
- Same validation rules as creation (minus floor_id)

### User Creation
- Required for convention invitations: first_name, last_name, email, mobile
- Required for self-registration: first_name, last_name, email, password
- Email must be globally unique
- Email cannot contain "jwpub.org"
- If email exists, connect existing user instead of creating duplicate
- Roles array required (at least one role) for convention invitations
- floor_ids required if FloorUser role assigned
- section_ids required if SectionUser role assigned

**Note:** The `mobile` field is required when convention managers invite users to conventions, but is not collected during self-registration.

### User Update
- Same fields as user creation for invitations
- Email uniqueness check excludes current user
- Same role/floor_ids/section_ids conditional requirements

### Password Requirements
- Uses Laravel's `Password::default()` rule
- In production: minimum 12 characters, mixed case, letters, numbers, symbols, uncompromised (checked against breach databases)
- In non-production: no additional password rules beyond `required`, `string`, `confirmed`

## Scheduled Tasks

### Daily Occupancy Reset
- Runs every day at 6:00 AM
- Resets occupancy to 0 for all sections
- Resets available_seats to number_of_seats for all sections (all seats available)
- Clears last_occupancy_updated_by
- Clears last_occupancy_updated_at

### Cleanup Unconfirmed Guest Conventions
- Runs every day at 3:00 AM
- Finds users with email_confirmed=false created more than 7 days ago
- Deletes conventions where these unconfirmed users are the Owner
- Cascading foreign keys handle cleanup of floors, sections, attendance, pivots
- If the user has no remaining conventions after cleanup, the user record is also deleted
- Prevents accumulation of abandoned guest-created conventions

## Email System

### Mailgun Integration
- All emails sent via Mailgun service
- Requires MAILGUN_DOMAIN and MAILGUN_SECRET in .env

### Email Types

#### User Invitation Email
- Mailable class: `App\Mail\UserInvitation`
- Sent when: New user created or existing user added to convention
- Contains: Signed URL with 24-hour expiration
- Template: Markdown email with user name, convention name, invitation URL, expiration
- Action: Set password and confirm email

#### Email Confirmation Email
- Mailable class: `App\Mail\EmailConfirmation`
- Sent when: User updates their email address (triggered by UserObserver)
- Contains: Signed URL with 24-hour expiration
- Template: Markdown email with user name, confirmation URL, expiration
- Action: Confirm new email address
- UserObserver automatically sets email_confirmed=false when email changes

#### Guest Convention Verification Email
- Mailable class: `App\Mail\GuestConventionVerification`
- Sent when: New user creates a convention as a guest (unauthenticated)
- Contains: Signed URL with 24-hour expiration
- Template: Markdown email with user name, convention name, verification URL
- Action: Set password, confirm email, log in, and redirect to convention

### Email Confirmation Status
- Green checkmark icon: Email confirmed
- Warning icon: Email not confirmed
- "Resend invitation" button available to managers
- Rate limited: 3 resends per 60 minutes

## Version Checking

### API Endpoint
- `GET /api/version/latest` - Public endpoint (no auth required)
- Fetches latest GitHub release info via VersionController
- Response cached for 5 minutes (300 seconds)
- Returns: version (tag_name), name, body, published_at, html_url

### Frontend Integration
- `use-app-version` hook polls for version updates
- `version-badge` component displays current app version
- `update-notification-modal` shows when new version is available
- App version shared via HandleInertiaRequests middleware (from git tag or VERSION file)

## Progressive Web App (PWA)

### Installation Support
- Web App Manifest file provided
- Service worker for offline capability
- Installation prompt/banner
- Step-by-step installation instructions for iOS and Android
- Native-like mobile experience

## Security Features

### Authentication
- Session-based authentication via Laravel Fortify
- "Remember me" option: persistent cookie
- Rate limiting: 5 login attempts per minute per IP (via Fortify limiter)

### Authorization
- Role-based access control enforced at middleware level
- Query scoping based on user roles (ScopeByRole middleware)
- Policies for Convention, Floor, Section, User entities
- Signed URLs for invitation links with expiration

### Security Event Logging
- SecurityEventListener logs security-relevant events to dedicated security log channel
- Logged events: failed login attempts, authorization failures, invalid signed URL access, rate limit violations
- Each log entry includes: event type, IP address, URL, user agent, and contextual data

### Input Validation
- All inputs validated server-side via Form Requests
- SanitizesInput trait applied to form requests for XSS prevention
  - Plain text fields: trimmed and HTML tags stripped
  - Rich text fields (other_info, information): trimmed, dangerous tags/attributes removed, basic formatting preserved
  - Excluded fields: password, password_confirmation, current_password
- CSRF protection on all state-changing requests
- SQL injection prevention via Eloquent ORM
- XSS prevention via Blade/React escaping
- Secure HTTP headers via SecureHeaders middleware
- CSP nonce via Vite::useCspNonce()

### Rate Limiting
- Login: 5 attempts per minute per IP (Fortify default)
- Invitation resend: 3 attempts per 60 minutes per user
- Password update: 6 attempts per minute
