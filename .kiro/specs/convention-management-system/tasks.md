# Implementation Plan: Convention Management System

## Overview

This implementation plan breaks down the Convention Management System into discrete, testable coding tasks. The system is a full-stack Laravel + React application with real-time occupancy tracking, attendance reporting, role-based access control, and PWA capabilities. Implementation follows a bottom-up approach: database → models → business logic → controllers → frontend.

## Tasks

- [x] 1. Database foundation and migrations
  - [x] 1.1 Create conventions table migration
    - Create migration with all fields: name, city, country, address, start_date, end_date, other_info
    - Add indexes for location (city, country) and dates
    - Add check constraint for end_date >= start_date
    - _Requirements: 1.1, 1.2, 23.1_

  - [x] 1.2 Create floors table migration
    - Create migration with convention_id foreign key and name field
    - Add ON DELETE CASCADE for convention_id
    - Add index on convention_id
    - _Requirements: 6.1, 6.2, 23.1_

  - [x] 1.3 Create sections table migration
    - Create migration with floor_id, name, number_of_seats, occupancy, available_seats
    - Add elder_friendly, handicap_friendly boolean fields
    - Add information text field
    - Add last_occupancy_updated_by and last_occupancy_updated_at fields
    - Add check constraints for occupancy (0-100) and available_seats (>= 0)
    - Add indexes on floor_id, occupancy, and accessibility fields
    - _Requirements: 6.3, 6.4, 6.5, 6.6, 6.7, 23.1_

  - [x] 1.4 Create users table migration (extend existing)
    - Verify existing users table has first_name, last_name, email, password
    - Add mobile, email_confirmed fields if not present
    - Add unique index on email
    - _Requirements: 4.1, 4.4, 23.1_

  - [x] 1.5 Create pivot tables migrations
    - Create convention_user pivot table with unique constraint
    - Create convention_user_roles pivot table with role enum and unique constraint
    - Create floor_user pivot table with unique constraint
    - Create section_user pivot table with unique constraint
    - Add appropriate indexes on all pivot tables
    - _Requirements: 5.2, 23.2_

  - [x] 1.6 Create attendance_periods table migration
    - Create migration with convention_id, date, period (enum: morning/afternoon), locked
    - Add unique constraint on (convention_id, date, period)
    - Add indexes on convention_id and (date, period)
    - _Requirements: 10.1, 10.2, 23.1_

  - [x] 1.7 Create attendance_reports table migration
    - Create migration with attendance_period_id, section_id, attendance, reported_by, reported_at
    - Add foreign keys with ON DELETE CASCADE
    - Add unique constraint on (attendance_period_id, section_id)
    - Add indexes on attendance_period_id and section_id
    - _Requirements: 10.4, 23.1_


- [ ] 2. Eloquent models and relationships
  - [ ] 2.1 Create Convention model with relationships
    - Define fillable fields and casts
    - Add floors() hasMany relationship
    - Add users() belongsToMany relationship via convention_user
    - Add attendancePeriods() hasMany relationship
    - Add userRoles() method to get roles for specific user
    - Add helper methods: hasRole(), hasAnyRole()
    - _Requirements: 5.2, 5.3_

  - [ ] 2.2 Write property test for Convention model
    - **Property 4: Convention Creator Role Assignment**
    - **Validates: Requirements 1.4**

  - [ ] 2.3 Create Floor model with relationships
    - Define fillable fields
    - Add convention() belongsTo relationship
    - Add sections() hasMany relationship
    - Add users() belongsToMany relationship via floor_user
    - _Requirements: 6.1, 6.2_

  - [ ] 2.4 Create Section model with relationships
    - Define fillable fields and casts
    - Add floor() belongsTo relationship
    - Add users() belongsToMany relationship via section_user
    - Add lastUpdatedBy() belongsTo User relationship
    - Add attendanceReports() hasMany relationship
    - _Requirements: 6.3, 6.4, 6.5, 6.6, 6.7_

  - [ ] 2.5 Write property test for Section model
    - **Property 23: Section Default Values**
    - **Validates: Requirements 6.6**

  - [ ] 2.6 Extend User model with convention relationships
    - Add conventions() belongsToMany relationship via convention_user
    - Add floors() belongsToMany relationship via floor_user
    - Add sections() belongsToMany relationship via section_user
    - Add rolesForConvention() method
    - Add hasRole(), hasAnyRole() helper methods
    - _Requirements: 5.2, 5.3_

  - [ ] 2.7 Create AttendancePeriod model with relationships
    - Define fillable fields and casts
    - Add convention() belongsTo relationship
    - Add reports() hasMany AttendanceReport relationship
    - Add isActive() method (returns !locked)
    - Add totalAttendance() method
    - Add reportedSectionsCount() method
    - _Requirements: 10.1, 10.2, 10.4_

  - [ ] 2.8 Create AttendanceReport model with relationships
    - Define fillable fields and casts
    - Add period() belongsTo AttendancePeriod relationship
    - Add section() belongsTo relationship
    - Add reportedBy() belongsTo User relationship
    - _Requirements: 10.4_


- [ ] 3. Form request validation classes
  - [ ] 3.1 Create StoreConventionRequest
    - Validate required fields: name, city, country, start_date, end_date
    - Validate optional fields: address, other_info
    - Add custom validation for date overlap detection
    - _Requirements: 1.1, 1.2, 1.3_

  - [ ] 3.2 Write property test for convention overlap detection
    - **Property 3: Convention Date Overlap Detection**
    - **Validates: Requirements 1.3**

  - [ ] 3.3 Create UpdateConventionRequest
    - Same validation as StoreConventionRequest
    - Exclude current convention from overlap check
    - _Requirements: 1.1, 1.2, 1.3_

  - [ ] 3.4 Create StoreFloorRequest
    - Validate required field: name
    - _Requirements: 6.1_

  - [ ] 3.5 Write property test for floor creation validation
    - **Property 19: Floor Creation Validation**
    - **Validates: Requirements 6.1**

  - [ ] 3.6 Create StoreSectionRequest
    - Validate required fields: name, number_of_seats
    - Validate optional fields: elder_friendly, handicap_friendly, information
    - Validate number_of_seats is positive integer
    - _Requirements: 6.3, 6.4, 6.5_

  - [ ] 3.7 Write property test for section creation validation
    - **Property 21: Section Creation Validation**
    - **Validates: Requirements 6.3**

  - [ ] 3.8 Create UpdateOccupancyRequest
    - Validate occupancy enum: 0, 10, 25, 50, 75, 100 (optional)
    - Validate available_seats integer >= 0 (optional)
    - Require at least one of occupancy or available_seats
    - _Requirements: 7.3, 7.6_

  - [ ] 3.9 Create StoreUserRequest
    - Validate required fields: first_name, last_name, email, mobile
    - Validate email uniqueness
    - Add custom rule to reject emails containing "jwpub.org"
    - Validate roles array (Owner, ConventionUser, FloorUser, SectionUser)
    - Validate floor_ids array if FloorUser role present
    - Validate section_ids array if SectionUser role present
    - _Requirements: 4.1, 4.2, 4.4, 5.1_

  - [ ] 3.10 Write property test for email domain restriction
    - **Property 13: Email Domain Restriction**
    - **Validates: Requirements 4.2**

  - [ ] 3.11 Create UpdateUserRequest
    - Same validation as StoreUserRequest
    - Exclude current user from email uniqueness check
    - _Requirements: 4.1, 4.2, 4.4_

  - [ ] 3.12 Create ReportAttendanceRequest
    - Validate attendance integer >= 0
    - Validate period_id exists in attendance_periods
    - _Requirements: 10.4_

  - [ ] 3.13 Create SetPasswordRequest
    - Validate password: min 8 chars, lowercase, uppercase, number, symbol
    - Validate password_confirmation matches
    - _Requirements: 21.4_

  - [ ] 3.14 Write property test for password validation
    - **Property 50: Password Validation Criteria**
    - **Validates: Requirements 21.4**

  - [ ] 3.15 Create SearchRequest
    - Validate optional floor_id exists
    - Validate optional elder_friendly boolean
    - Validate optional handicap_friendly boolean
    - _Requirements: 16.2, 16.3_


- [ ] 4. Business logic actions and services
  - [ ] 4.1 Create CreateConventionAction
    - Accept validated data and creator user
    - Create convention record
    - Attach creator as Owner and ConventionUser roles
    - Create attendance periods for date range (lazy creation)
    - Return created convention
    - _Requirements: 1.4, 1.5, 10.3_

  - [ ] 4.2 Create InviteUserAction
    - Accept validated data and convention
    - Check if user exists by email
    - If exists, attach to convention; if not, create new user
    - Attach roles to user via convention_user_roles
    - Attach to floors if FloorUser role
    - Attach to sections if SectionUser role
    - Generate signed invitation URL (24h expiration)
    - Send invitation email via Mailgun
    - Return user
    - _Requirements: 3.1, 3.2, 4.3_

  - [ ] 4.3 Write property test for user deduplication
    - **Property 14: User Deduplication by Email**
    - **Validates: Requirements 4.3**

  - [ ] 4.4 Create UpdateOccupancyAction
    - Accept section, data (occupancy or available_seats), and user
    - Calculate occupancy percentage if available_seats provided
    - Calculate available_seats if occupancy percentage provided
    - Update section occupancy and available_seats
    - Record last_occupancy_updated_by and timestamp
    - Return updated section
    - _Requirements: 7.3, 7.5, 7.7, 7.8_

  - [ ] 4.5 Write property test for occupancy calculation
    - **Property 27: Available Seats Occupancy Calculation**
    - **Validates: Requirements 7.7**

  - [ ] 4.6 Create ExportConventionAction
    - Accept convention and format (.xlsx, .docx, .md)
    - Load all related data (floors, sections, users, attendance)
    - Delegate to format-specific exporter
    - Return file path for download
    - _Requirements: 20.1, 20.2, 20.3_

  - [ ] 4.7 Create AttendanceReportService
    - Implement startReport() method
    - Determine current period (morning/afternoon based on time)
    - Validate max 2 reports per day
    - Create or retrieve attendance period
    - Return active period
    - _Requirements: 10.5, 10.6_

  - [ ] 4.8 Write property test for max reports per day
    - **Property 32: Maximum Two Reports Per Day**
    - **Validates: Requirements 10.6**

  - [ ] 4.9 Implement AttendanceReportService stopReport() method
    - Accept attendance period
    - Set locked = true
    - Prevent further updates
    - _Requirements: 11.1, 11.3_

  - [ ] 4.10 Implement AttendanceReportService reportAttendance() method
    - Accept section, period, attendance value, and user
    - Validate user has permission for section
    - Create or update attendance report
    - Record reported_by and reported_at
    - Enforce update restrictions (only original reporter can update)
    - _Requirements: 10.4, 11.5_

  - [ ] 4.11 Write property test for attendance update restrictions
    - **Property 36: Section User Attendance Update Restriction**
    - **Validates: Requirements 11.5, 11.6**


- [ ] 5. Export system implementation
  - [ ] 5.1 Install export dependencies
    - Add maatwebsite/excel package via composer
    - Add phpoffice/phpword package via composer
    - _Requirements: 20.4, 20.5_

  - [ ] 5.2 Create ConventionExcelExport class
    - Implement FromCollection, WithHeadings, WithMultipleSheets interfaces
    - Create sheets: Convention, Floors & Sections, Attendance History, Users
    - Serialize all convention data into Excel format
    - _Requirements: 20.3, 20.4, 25.1_

  - [ ] 5.3 Create ConventionWordExport class
    - Use phpoffice/phpword to generate .docx
    - Include convention details, floors, sections, attendance, users
    - Format with titles, tables, and proper structure
    - _Requirements: 20.3, 20.5, 25.2_

  - [ ] 5.4 Create ConventionMarkdownExport class
    - Generate Markdown with valid syntax
    - Include convention details, floors, sections, attendance, users
    - Use tables for structured data
    - _Requirements: 20.3, 20.6, 25.3_

  - [ ] 5.5 Write property test for export data completeness
    - **Property 48: Export Data Completeness**
    - **Validates: Requirements 20.3**

  - [ ] 5.6 Write property test for export format serialization
    - **Property 53: Export Format Serialization**
    - **Validates: Requirements 25.1, 25.2, 25.3**


- [ ] 6. Middleware and authorization
  - [ ] 6.1 Create EnsureConventionAccess middleware
    - Verify user has any role for the convention
    - Abort with 403 if no access
    - _Requirements: 5.2_

  - [ ] 6.2 Create EnsureOwnerRole middleware
    - Verify user has Owner role for the convention
    - Abort with 403 if not owner
    - _Requirements: 5.4, 12.4_

  - [ ] 6.3 Create ScopeByRole middleware
    - Filter query results based on user's role scope
    - Add scoped_floor_ids for FloorUser
    - Add scoped_section_ids for SectionUser
    - Pass through for Owner and ConventionUser
    - _Requirements: 5.5, 5.6, 5.7, 12.1, 12.2, 12.3_

  - [ ] 6.4 Write property test for role-based data scoping
    - **Property 18: Role-Based Data Scoping**
    - **Validates: Requirements 5.5, 5.6, 5.7, 12.1, 12.2, 12.3**

  - [ ] 6.5 Create ConventionPolicy
    - Implement view() method (user has any role)
    - Implement update() method (Owner or ConventionUser)
    - Implement delete() method (Owner only)
    - Implement export() method (Owner only)
    - _Requirements: 5.4, 5.5, 12.4, 20.1_

  - [ ] 6.6 Create FloorPolicy
    - Implement view() method (role-based scoping)
    - Implement update() method (Owner, ConventionUser, or assigned FloorUser)
    - Implement delete() method (Owner or ConventionUser only)
    - _Requirements: 13.1, 13.2_

  - [ ] 6.7 Write property test for FloorUser permissions
    - **Property 37: Role-Based Permission Enforcement**
    - **Property 38: FloorUser Section Management**
    - **Validates: Requirements 13.1, 13.2, 13.3**

  - [ ] 6.8 Create SectionPolicy
    - Implement view() method (role-based scoping)
    - Implement update() method (Owner, ConventionUser, assigned FloorUser, or assigned SectionUser)
    - Implement delete() method (Owner, ConventionUser, or assigned FloorUser)
    - _Requirements: 14.1, 14.2_

  - [ ] 6.9 Write property test for SectionUser restrictions
    - **Property 39: SectionUser Edit Restrictions**
    - **Property 40: SectionUser User Management Scope**
    - **Validates: Requirements 14.2, 14.3**

  - [ ] 6.10 Create UserPolicy
    - Implement view() method (role-based scoping)
    - Implement update() method (based on scope)
    - Implement delete() method (based on scope)
    - _Requirements: 15.6, 15.7_


- [ ] 7. Controllers and routes
  - [ ] 7.1 Create ConventionController
    - Implement index() - list user's conventions
    - Implement create() - show creation form
    - Implement store() - create convention with validation
    - Implement show() - display convention with role-scoped data
    - Implement update() - update convention details
    - Implement destroy() - delete convention (Owner only)
    - Implement export() - export convention data
    - Apply middleware: auth, EnsureConventionAccess, EnsureOwnerRole (where needed)
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 12.4, 20.1, 20.2_

  - [ ] 7.2 Create FloorController
    - Implement index() - list floors (role-scoped)
    - Implement store() - create floor
    - Implement update() - update floor name
    - Implement destroy() - delete floor and cascade sections
    - Apply middleware: auth, EnsureConventionAccess, ScopeByRole
    - _Requirements: 6.1, 6.2, 13.1, 13.2_

  - [ ] 7.3 Create SectionController
    - Implement index() - list sections in floor
    - Implement show() - display section detail with occupancy controls
    - Implement store() - create section
    - Implement update() - update section attributes
    - Implement updateOccupancy() - update occupancy percentage
    - Implement setFull() - set occupancy to 100%
    - Implement destroy() - delete section
    - Apply middleware: auth, EnsureConventionAccess, ScopeByRole
    - _Requirements: 6.3, 6.4, 6.5, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

  - [ ] 7.4 Create UserController
    - Implement index() - list users (role-scoped)
    - Implement store() - create/invite user
    - Implement update() - update user details
    - Implement destroy() - remove user from convention
    - Implement resendInvitation() - resend invitation email
    - Apply middleware: auth, EnsureConventionAccess, ScopeByRole
    - Apply throttle middleware to resendInvitation (3 per 60 minutes)
    - _Requirements: 3.8, 3.9, 4.1, 4.2, 4.3, 4.4, 17.1, 17.2_

  - [ ] 7.5 Write property test for invitation resend rate limiting
    - **Property 11: Invitation Resend Rate Limiting**
    - **Validates: Requirements 3.9**

  - [ ] 7.6 Create AttendanceController
    - Implement start() - start attendance report period
    - Implement stop() - lock attendance period
    - Implement report() - submit section attendance
    - Apply middleware: auth, EnsureConventionAccess
    - _Requirements: 10.5, 10.6, 10.7, 10.8, 11.1, 11.2, 11.3_

  - [ ] 7.7 Create SearchController
    - Implement index() - search available sections with filters
    - Filter by floor (optional)
    - Filter by elder_friendly (optional)
    - Filter by handicap_friendly (optional)
    - Filter occupancy < 90%
    - Sort by occupancy ascending
    - Apply middleware: auth (no role-based filtering)
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.8_

  - [ ] 7.8 Write property test for search occupancy filter
    - **Property 42: Search Occupancy Filter**
    - **Property 43: Search Result Ordering**
    - **Validates: Requirements 16.4, 16.5**

  - [ ] 7.9 Create Auth/InvitationController
    - Implement show() - display password creation form
    - Implement store() - set password and confirm email
    - Apply signed middleware to show route
    - _Requirements: 3.3, 3.4_

  - [ ] 7.10 Write property test for password confirmation
    - **Property 8: Password Confirmation Sets Email Verified**
    - **Validates: Requirements 3.4**

  - [ ] 7.11 Define web routes
    - Add convention routes with appropriate middleware
    - Add floor routes with appropriate middleware
    - Add section routes with appropriate middleware
    - Add user routes with appropriate middleware
    - Add attendance routes with appropriate middleware
    - Add search routes with appropriate middleware
    - Add invitation routes with signed middleware
    - Apply throttle middleware to login (5 per minute)
    - _Requirements: 2.6, 3.9, 21.6, 21.7_

  - [ ] 7.12 Write property test for login rate limiting
    - **Property 6: Login Rate Limiting**
    - **Validates: Requirements 2.4, 2.6**


- [ ] 8. Email system with Mailgun
  - [ ] 8.1 Configure Mailgun in .env.example
    - Add MAIL_MAILER=mailgun
    - Add MAILGUN_DOMAIN placeholder
    - Add MAILGUN_SECRET placeholder
    - Add MAIL_FROM_ADDRESS and MAIL_FROM_NAME
    - _Requirements: 22.3, 22.4, 22.5_

  - [ ] 8.2 Create UserInvitation mailable
    - Accept user, convention, and invitation URL
    - Use markdown template
    - Include user name, convention name, invitation URL, expiration time
    - _Requirements: 3.1, 3.2_

  - [ ] 8.3 Write property test for invitation email delivery
    - **Property 7: User Invitation Email Delivery**
    - **Validates: Requirements 3.1, 3.2**

  - [ ] 8.4 Create EmailConfirmation mailable
    - Accept user and confirmation URL
    - Use markdown template
    - Include user name, confirmation URL, expiration time
    - _Requirements: 3.5_

  - [ ] 8.5 Write property test for email update confirmation
    - **Property 9: Email Update Triggers Confirmation**
    - **Validates: Requirements 3.5**

  - [ ] 8.6 Create markdown email templates
    - Create resources/views/emails/user-invitation.blade.php
    - Create resources/views/emails/email-confirmation.blade.php
    - Use Laravel markdown components
    - _Requirements: 22.1_

  - [ ] 8.7 Add User model observer for email updates
    - Listen for email attribute changes
    - Send confirmation email when email is updated
    - Set email_confirmed to false
    - _Requirements: 3.5_


- [ ] 9. Scheduled tasks
  - [ ] 9.1 Create daily occupancy reset command
    - Create artisan command: ResetDailyOccupancy
    - Reset occupancy to 0 for all sections
    - Reset available_seats to 0 for all sections
    - Clear last_occupancy_updated_by and last_occupancy_updated_at
    - _Requirements: 8.1, 8.2, 8.3_

  - [ ] 9.2 Write property test for daily occupancy reset
    - **Property 28: Daily Occupancy Reset**
    - **Validates: Requirements 8.2, 8.3**

  - [ ] 9.3 Schedule daily reset in Kernel
    - Add schedule in app/Console/Kernel.php
    - Run daily at 6:00 AM
    - _Requirements: 8.1_


- [ ] 10. TypeScript types and interfaces
  - [ ] 10.1 Create convention types
    - Define Convention interface with all fields
    - Define Floor interface with convention relationship
    - Define Section interface with floor relationship and accessibility fields
    - Define AttendancePeriod interface
    - Define AttendanceReport interface
    - Add to resources/js/types/convention.ts
    - _Requirements: All data model requirements_

  - [ ] 10.2 Create user and role types
    - Define User interface with convention-specific fields
    - Define Role enum (Owner, ConventionUser, FloorUser, SectionUser)
    - Define ConventionUser interface (user with roles)
    - Add to resources/js/types/user.ts
    - _Requirements: 5.1_

  - [ ] 10.3 Create shared props types
    - Define PageProps interface with auth user
    - Define Flash message types
    - Define Errors type for validation
    - Add to resources/js/types/index.ts
    - _Requirements: 24.2, 24.4_


- [ ] 11. Custom React hooks
  - [ ] 11.1 Create useConventionRole hook
    - Accept convention as parameter
    - Return isOwner, isConventionUser, isFloorUser, isSectionUser booleans
    - Return hasFloorAccess(floorId) function
    - Return hasSectionAccess(sectionId) function
    - Use auth user from Inertia shared props
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

  - [ ] 11.2 Create useOccupancyColor hook
    - Accept occupancy percentage as parameter
    - Return Tailwind color class based on percentage
    - 0-25%: green, 26-50%: dark-green, 51-75%: yellow, 76-90%: orange, 91-100%: red
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [ ] 11.3 Write property test for occupancy color coding
    - **Property 29: Occupancy Color Coding**
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5**

  - [ ] 11.3 Create useAttendanceReport hook
    - Accept convention as parameter
    - Return activePeriod (current active period or null)
    - Return canStart boolean (max 2 per day check)
    - Return canStop boolean (user is ConventionUser)
    - Return reportedCount (sections that have reported)
    - Return totalCount (total sections in convention)
    - _Requirements: 10.6, 10.7, 10.8_

  - [ ] 11.4 Write property test for attendance calculations
    - **Property 33: Attendance Total Calculation**
    - **Property 34: Reported Sections Counter**
    - **Validates: Requirements 10.7, 10.8**


- [ ] 12. UI components
  - [ ] 12.1 Create ConventionCard component
    - Accept convention prop (name, dates, location)
    - Display formatted date range
    - Display city and country
    - Make clickable to navigate to convention detail
    - Use Wayfinder type-safe routing
    - _Requirements: 18.1_

  - [ ] 12.2 Create FloorRow component
    - Accept floor, sections, userRole props
    - Display floor name
    - Show expandable sections list
    - Display occupancy color indicator
    - Show edit/delete actions based on role
    - _Requirements: 12.6, 12.7, 13.1, 13.2_

  - [ ] 12.3 Create SectionCard component
    - Accept section prop (name, occupancy, available_seats, accessibility)
    - Display color-coded occupancy icon
    - Display accessibility badges (elder_friendly, handicap_friendly)
    - Make clickable to navigate to section detail
    - _Requirements: 7.1, 9.6, 9.7, 16.7_

  - [ ] 12.4 Create OccupancyDropdown component
    - Accept currentOccupancy and onUpdate props
    - Display dropdown with options: 0%, 10%, 25%, 50%, 75%, 100%
    - Auto-save on selection
    - _Requirements: 7.2, 7.3_

  - [ ] 12.5 Write property test for occupancy dropdown auto-save
    - **Property 25: Occupancy Dropdown Auto-Save**
    - **Validates: Requirements 7.3**

  - [ ] 12.6 Create FullButton component
    - Accept section and onUpdate props
    - Display prominent "FULL" button
    - Set occupancy to 100% on click
    - _Requirements: 7.4, 7.5_

  - [ ] 12.7 Write property test for full button
    - **Property 26: Full Button Sets 100% Occupancy**
    - **Validates: Requirements 7.5**

  - [ ] 12.8 Create AvailableSeatsInput component
    - Accept section and onUpdate props
    - Display numeric input field
    - Display "Send" button
    - Calculate occupancy from available seats on submit
    - _Requirements: 7.6, 7.7_

  - [ ] 12.9 Create AttendanceReportBanner component
    - Accept activePeriod, totalAttendance, reportedCount, totalCount props
    - Display "X of Y sections reported" counter
    - Display total attendance
    - Display "Stop attendance report" button
    - Show confirmation warning if not all sections reported
    - _Requirements: 10.7, 10.8, 11.1, 11.2_

  - [ ] 12.10 Create UserRow component
    - Accept user prop (name, email, roles, email_confirmed)
    - Display email confirmation icon (green checkmark or warning)
    - Display role badges
    - Display "Resend invitation" button
    - Display edit/delete actions
    - _Requirements: 3.6, 3.7, 3.8_

  - [ ] 12.11 Write property test for email confirmation display
    - **Property 10: Email Confirmation Status Display**
    - **Validates: Requirements 3.6, 3.7**

  - [ ] 12.12 Create ExportDropdown component
    - Accept convention and onExport props
    - Display format dropdown: .xlsx, .docx, Markdown
    - Trigger download on selection
    - Only visible to Owner role
    - _Requirements: 20.1, 20.2_

  - [ ] 12.13 Create RoleBadge component
    - Accept role prop (Owner, ConventionUser, FloorUser, SectionUser)
    - Display color-coded badge
    - _Requirements: 5.1_

  - [ ] 12.14 Create OccupancyIndicator component
    - Accept occupancy prop (0-100)
    - Return color class and icon
    - Use useOccupancyColor hook
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_


- [ ] 13. Inertia page components
  - [ ] 13.1 Create conventions/index page
    - List user's conventions
    - Display ConventionCard for each convention
    - Add "Create Convention" button
    - Use app-layout
    - _Requirements: 18.1_

  - [ ] 13.2 Create conventions/create page
    - Display convention creation form
    - Include all required fields: name, city, country, start_date, end_date
    - Include optional fields: address, other_info
    - Display inline validation errors
    - Use app-layout
    - _Requirements: 1.1, 1.2, 24.2, 24.3_

  - [ ] 13.3 Write property test for form validation errors
    - **Property 51: Validation Error Display**
    - **Property 52: Form Input Preservation**
    - **Validates: Requirements 24.2, 24.4**

  - [ ] 13.4 Create conventions/show page
    - Display convention details
    - Display floors datatable with expandable sections
    - Display export dropdown (Owner only)
    - Display delete button with confirmation (Owner only)
    - Display attendance report controls (ConventionUser)
    - Apply role-based visibility
    - Use app-layout
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7_

  - [ ] 13.5 Create floors/index page
    - List floors for convention (role-scoped)
    - Display add floor button (ConventionUser only)
    - Display occupancy color coding
    - Use app-layout
    - _Requirements: 13.1, 13.2, 15.2, 15.3_

  - [ ] 13.6 Create sections/show page
    - Display section details
    - Display OccupancyDropdown component
    - Display FullButton component
    - Display AvailableSeatsInput component
    - Display last update information in footer
    - Display attendance reporting input (if active period)
    - Use app-layout
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.9_

  - [ ] 13.7 Create users/index page
    - List users (role-scoped)
    - Display UserRow for each user
    - Display add user button
    - Use app-layout
    - _Requirements: 3.6, 3.7, 3.8, 15.6, 15.7_

  - [ ] 13.8 Create search/index page
    - Display floor filter dropdown
    - Display elder_friendly checkbox
    - Display handicap_friendly checkbox
    - Display search results list (occupancy < 90%)
    - Sort results by occupancy ascending
    - Display floor name, section name, occupancy icon
    - Paginate results
    - Mobile-optimized layout
    - Use app-layout
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7_

  - [ ] 13.9 Write property test for search accessibility
    - **Property 41: Search Accessibility**
    - **Property 44: Search Role-Agnostic Results**
    - **Validates: Requirements 16.1, 16.8**

  - [ ] 13.10 Create auth/invitation page
    - Display password creation form
    - Display password strength indicator
    - Validate password criteria
    - Display inline validation errors
    - Use auth-layout
    - _Requirements: 3.3, 21.4, 24.2_

  - [ ] 13.11 Update login page (extend existing)
    - Add "Remember me" checkbox
    - _Requirements: 2.2, 2.3_

  - [ ] 13.12 Write property test for remember me session
    - **Property 5: Remember Me Session Duration**
    - **Validates: Requirements 2.3**


- [ ] 14. Navigation and layout
  - [ ] 14.1 Update app-layout navigation
    - Add navigation links: Conventions, Floors, Sections, Users, Search
    - Scope navigation visibility based on user role
    - Use Wayfinder type-safe routing
    - _Requirements: 18.2, 18.3_

  - [ ] 14.2 Write property test for navigation visibility
    - **Property 47: Navigation Visibility by Role**
    - **Validates: Requirements 18.3**

  - [ ] 14.2 Implement mobile-first responsive design
    - Ensure all pages work on mobile (375px minimum)
    - Test at breakpoints: 375px, 768px, 1024px, 1440px
    - Optimize datatables for mobile
    - Optimize forms for mobile
    - _Requirements: 18.4_

  - [ ] 14.3 Implement drill-down navigation
    - Convention → Floor → Section navigation flow
    - Breadcrumb navigation
    - Back button support
    - _Requirements: 18.5_


- [ ] 15. Progressive Web App (PWA) implementation
  - [ ] 15.1 Create Web App Manifest
    - Create public/manifest.json
    - Define name, short_name, description
    - Set start_url, display, background_color, theme_color
    - Set orientation to portrait
    - Define icons array (72x72 to 512x512)
    - _Requirements: 19.1_

  - [ ] 15.2 Generate PWA icons
    - Create icons in sizes: 72x72, 96x96, 128x128, 144x144, 152x152, 192x192, 384x384, 512x512
    - Save to public/icons/ directory
    - Use convention management theme
    - _Requirements: 19.1_

  - [ ] 15.3 Create service worker
    - Create public/sw.js
    - Implement install event with cache
    - Implement fetch event with cache-first strategy
    - Cache app shell and critical assets
    - _Requirements: 19.2_

  - [ ] 15.4 Register service worker
    - Add service worker registration to app.blade.php
    - Check for service worker support
    - Register sw.js
    - _Requirements: 19.2_

  - [ ] 15.5 Add manifest link to HTML
    - Add manifest link to app.blade.php
    - Add theme-color meta tag
    - Add apple-touch-icon link
    - _Requirements: 19.1_

  - [ ] 15.6 Create InstallPrompt component
    - Listen for beforeinstallprompt event
    - Display install button
    - Show installation instructions dialog
    - Include iOS (Safari) instructions
    - Include Android (Chrome) instructions
    - _Requirements: 19.3, 19.4, 19.5_

  - [ ] 15.7 Add InstallPrompt to app layout
    - Display in header or settings
    - Show only when PWA not installed
    - _Requirements: 19.3_


- [ ] 16. Security and validation implementation
  - [ ] 16.1 Implement CSRF protection
    - Verify CSRF middleware is applied to all state-changing routes
    - Ensure Inertia includes CSRF token in headers
    - _Requirements: 21.3_

  - [ ] 16.2 Write property test for CSRF protection
    - **Property 49: CSRF Protection**
    - **Validates: Requirements 21.3**

  - [ ] 16.2 Implement input sanitization
    - Verify all Form Requests sanitize inputs
    - Add HTML purification where needed
    - _Requirements: 21.2_

  - [ ] 16.3 Configure Content Security Policy headers
    - Add secure-headers configuration
    - Set CSP directives for scripts, styles, images
    - _Requirements: 21.1_

  - [ ] 16.4 Implement signed URL verification
    - Verify signed middleware on invitation routes
    - Add expiration handling
    - Add error messages for expired/invalid signatures
    - _Requirements: 21.5_

  - [ ] 16.5 Add destructive action confirmations
    - Add confirmation dialogs for delete actions
    - Add confirmation for stopping attendance report early
    - Add confirmation for convention deletion
    - _Requirements: 17.3, 21.8_

  - [ ] 16.6 Implement security logging
    - Log failed login attempts
    - Log authorization failures (403 errors)
    - Log invalid signed URL access
    - Log rate limit violations
    - _Requirements: 21.9_


- [ ] 17. Database seeders and factories
  - [ ] 17.1 Create ConventionFactory
    - Generate realistic convention data
    - Include date range generation
    - Add withOwner() state method
    - _Requirements: 23.3_

  - [ ] 17.2 Create FloorFactory
    - Generate floor names
    - Associate with convention
    - _Requirements: 23.3_

  - [ ] 17.3 Create SectionFactory
    - Generate section names and capacities
    - Set random accessibility features
    - Associate with floor
    - Initialize occupancy to 0
    - _Requirements: 23.3_

  - [ ] 17.4 Create AttendancePeriodFactory
    - Generate periods for convention dates
    - Set morning/afternoon periods
    - _Requirements: 23.3_

  - [ ] 17.5 Create DemoSeeder
    - Create demo Owner account
    - Create sample convention with structure
    - Create sample floors and sections
    - Create sample users with various roles
    - _Requirements: 23.4_

  - [ ] 17.6 Document seeder usage
    - Add instructions to README
    - Document demo account credentials
    - _Requirements: 23.4_


- [ ] 18. Testing infrastructure and property-based tests
  - [ ] 18.1 Install property-based testing dependencies
    - Add pest-plugin-faker for PHP (if not present)
    - Add fast-check for TypeScript via npm
    - _Requirements: Testing strategy_

  - [ ] 18.2 Create test helpers
    - Create ConventionTestHelper with createConventionWithStructure()
    - Create ConventionTestHelper with createUserWithRole()
    - Create test helper for authentication
    - _Requirements: Testing strategy_

  - [ ] 18.3 Write remaining property-based tests for conventions
    - **Property 1: Convention Creation Requires All Mandatory Fields**
    - **Property 2: Optional Fields Are Accepted**
    - _Requirements: 1.1, 1.2_

  - [ ] 18.4 Write property-based tests for roles
    - **Property 16: Multiple Role Assignment**
    - **Property 17: Owner Role Inherits ConventionUser Permissions**
    - _Requirements: 5.3, 5.4_

  - [ ] 18.5 Write property-based tests for floor-section relationships
    - **Property 20: Floor-Convention Association**
    - **Property 22: Section Optional Fields**
    - _Requirements: 6.2, 6.4, 6.5_

  - [ ] 18.6 Write property-based tests for occupancy metadata
    - **Property 24: Occupancy Update Metadata Recording**
    - _Requirements: 6.7, 7.8_

  - [ ] 18.7 Write property-based tests for attendance periods
    - **Property 30: Two Attendance Periods Per Day**
    - **Property 31: Attendance Report Data Storage**
    - **Property 35: Attendance Period Locking**
    - _Requirements: 10.1, 10.2, 10.4, 11.3_

  - [ ] 18.8 Write property-based tests for user management
    - **Property 12: Email Uniqueness Enforcement**
    - **Property 15: User Required Fields Validation**
    - **Property 45: User Deletion Cascade**
    - **Property 46: User Record Cleanup**
    - _Requirements: 4.1, 4.4, 17.1, 17.2_

  - [ ] 18.9 Write property-based tests for export validation
    - **Property 54: Export Data Validation**
    - _Requirements: 25.5_


- [ ] 19. Unit tests for critical functionality
  - [ ] 19.1 Write unit tests for convention creation
    - Test valid convention creation
    - Test missing required fields
    - Test date overlap detection
    - Test creator role assignment
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [ ] 19.2 Write unit tests for user invitation flow
    - Test new user invitation
    - Test existing user connection
    - Test email delivery
    - Test signed URL generation
    - Test password setting
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 4.3_

  - [ ] 19.3 Write unit tests for occupancy updates
    - Test occupancy dropdown update
    - Test full button
    - Test available seats calculation
    - Test metadata recording
    - _Requirements: 7.3, 7.5, 7.7, 7.8_

  - [ ] 19.4 Write unit tests for attendance reporting
    - Test starting report
    - Test max 2 reports per day
    - Test reporting attendance
    - Test locking period
    - Test update restrictions
    - _Requirements: 10.5, 10.6, 10.7, 10.8, 11.3, 11.5_

  - [ ] 19.5 Write unit tests for role-based access
    - Test Owner permissions
    - Test ConventionUser permissions
    - Test FloorUser scoping
    - Test SectionUser scoping
    - _Requirements: 5.4, 5.5, 5.6, 5.7_

  - [ ] 19.6 Write unit tests for search functionality
    - Test occupancy filter
    - Test accessibility filters
    - Test result ordering
    - Test role-agnostic access
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.8_

  - [ ] 19.7 Write unit tests for export functionality
    - Test Excel export
    - Test Word export
    - Test Markdown export
    - Test data completeness
    - _Requirements: 20.3, 20.4, 20.5, 20.6_

  - [ ] 19.8 Write unit tests for validation
    - Test email domain restriction
    - Test password criteria
    - Test form validation errors
    - Test input preservation
    - _Requirements: 4.2, 21.4, 24.2, 24.4_


- [ ] 20. Frontend component tests
  - [ ] 20.1 Write tests for ConventionCard component
    - Test rendering with valid props
    - Test date formatting
    - Test navigation on click
    - _Requirements: 18.1_

  - [ ] 20.2 Write tests for OccupancyDropdown component
    - Test dropdown options
    - Test auto-save on selection
    - Test current value display
    - _Requirements: 7.2, 7.3_

  - [ ] 20.3 Write tests for useConventionRole hook
    - Test role detection
    - Test floor access checking
    - Test section access checking
    - _Requirements: 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

  - [ ] 20.4 Write tests for useOccupancyColor hook
    - Test color mapping for all ranges
    - Test boundary values
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [ ] 20.5 Write tests for useAttendanceReport hook
    - Test active period detection
    - Test canStart calculation
    - Test reported count calculation
    - _Requirements: 10.6, 10.7, 10.8_

  - [ ] 20.6 Write tests for UserRow component
    - Test email confirmation icon display
    - Test role badges display
    - Test resend invitation button visibility
    - _Requirements: 3.6, 3.7, 3.8_

  - [ ] 20.7 Write tests for search page
    - Test filter inputs
    - Test result display
    - Test occupancy filtering
    - Test sorting
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5_


- [ ] 21. Documentation and setup
  - [ ] 21.1 Update .env.example
    - Add all Mailgun configuration keys
    - Add database configuration
    - Add session configuration
    - Add app configuration
    - Document all required variables
    - _Requirements: 22.5, 22.6_

  - [ ] 21.2 Create comprehensive README
    - Add project overview
    - Add technology stack section
    - Add installation instructions
    - Add configuration instructions
    - Add Mailgun setup guide
    - Add demo account information
    - Add development commands
    - Add testing instructions
    - _Requirements: 23.3, 23.4_

  - [ ] 21.3 Document architectural decisions
    - Document role-based access control design
    - Document occupancy tracking approach
    - Document attendance reporting flow
    - Document PWA implementation
    - Document export system design
    - _Requirements: 23.5_

  - [ ] 21.4 Create API documentation
    - Document all controller endpoints
    - Document request/response formats
    - Document validation rules
    - Document error responses
    - _Requirements: Documentation best practices_

  - [ ] 21.5 Create user guide
    - Document convention creation flow
    - Document user invitation process
    - Document occupancy tracking usage
    - Document attendance reporting process
    - Document search functionality
    - Document PWA installation
    - _Requirements: User documentation_


- [ ] 22. Integration and final wiring
  - [ ] 22.1 Wire all components together
    - Verify all routes are registered
    - Verify all middleware is applied correctly
    - Verify all policies are registered
    - Verify all Inertia pages are connected
    - _Requirements: All integration requirements_

  - [ ] 22.2 Configure Wayfinder
    - Ensure all controllers are scanned
    - Generate type-safe route actions
    - Verify frontend can import actions
    - _Requirements: Type-safe routing_

  - [ ] 22.3 Test complete user flows
    - Test convention creation to deletion flow
    - Test user invitation to login flow
    - Test occupancy tracking flow
    - Test attendance reporting flow
    - Test search and navigation flow
    - Test export flow
    - Test PWA installation flow
    - _Requirements: All user stories_

  - [ ] 22.4 Verify role-based access across all pages
    - Test Owner access to all features
    - Test ConventionUser access and restrictions
    - Test FloorUser scoping and restrictions
    - Test SectionUser scoping and restrictions
    - _Requirements: 5.4, 5.5, 5.6, 5.7, 12.1, 12.2, 12.3_

  - [ ] 22.5 Test mobile responsiveness
    - Test all pages at 375px width
    - Test all pages at 768px width
    - Test all pages at 1024px width
    - Test touch interactions
    - Test PWA on mobile devices
    - _Requirements: 18.4_

  - [ ] 22.6 Performance optimization
    - Optimize database queries (eager loading)
    - Add appropriate indexes
    - Optimize frontend bundle size
    - Test page load times
    - _Requirements: Performance best practices_

  - [ ] 22.7 Security audit
    - Verify all authorization checks
    - Verify CSRF protection
    - Verify input validation
    - Verify rate limiting
    - Verify signed URLs
    - Test for common vulnerabilities
    - _Requirements: 21.1, 21.2, 21.3, 21.4, 21.5, 21.6, 21.7_


- [ ] 23. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property-based tests validate universal correctness properties (54 total properties)
- Unit tests validate specific examples and edge cases
- Implementation follows bottom-up approach: database → models → business logic → controllers → frontend
- All 25 requirements from the requirements document are covered
- All 54 correctness properties from the design document have corresponding test tasks
- Wayfinder will auto-generate type-safe routing after controllers are created
- PWA functionality enables native-like mobile experience
- Role-based access control is enforced at multiple layers (middleware, policies, UI)

## Property Coverage Summary

The following properties are covered by test tasks throughout the implementation:

**Convention Management (Properties 1-4)**: Convention creation, validation, overlap detection, role assignment
**Authentication (Properties 5-11)**: Session management, rate limiting, invitations, email confirmation
**User Management (Properties 12-16)**: Email uniqueness, domain restriction, deduplication, role assignment
**Authorization (Properties 17-18)**: Role inheritance, data scoping
**Floor & Section (Properties 19-24)**: Creation validation, relationships, default values, metadata
**Occupancy (Properties 25-29)**: Dropdown, full button, calculation, reset, color coding
**Attendance (Properties 30-36)**: Periods, reports, locking, restrictions, calculations
**Permissions (Properties 37-40)**: Role-based editing, floor/section management
**Search (Properties 41-44)**: Accessibility, filtering, ordering, role-agnostic
**User Operations (Properties 45-46)**: Deletion cascade, record cleanup
**Navigation (Property 47)**: Visibility by role
**Export (Properties 48, 53-54)**: Data completeness, serialization, validation
**Security (Properties 49-52)**: CSRF, password validation, error display, input preservation

## Implementation Order Rationale

1. **Database First**: Establish data structure and relationships
2. **Models Second**: Define Eloquent models with relationships and helper methods
3. **Validation Third**: Create Form Requests to ensure data integrity
4. **Business Logic Fourth**: Implement Actions and Services for complex operations
5. **Export System**: Standalone feature that can be developed in parallel
6. **Authorization Fifth**: Implement middleware and policies for access control
7. **Controllers Sixth**: Wire business logic to HTTP endpoints
8. **Email System**: Configure Mailgun and create mailables
9. **Scheduled Tasks**: Implement daily occupancy reset
10. **Frontend Types**: Define TypeScript interfaces
11. **Frontend Hooks**: Create reusable stateful logic
12. **UI Components**: Build reusable React components
13. **Pages**: Assemble components into full pages
14. **Navigation**: Wire pages together with routing
15. **PWA**: Add progressive web app capabilities
16. **Security**: Implement security measures
17. **Testing**: Comprehensive test coverage
18. **Documentation**: Complete setup and usage guides
19. **Integration**: Wire everything together and test end-to-end

This order minimizes dependencies and allows for incremental testing at each stage.
