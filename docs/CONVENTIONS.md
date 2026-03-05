# Convention Management System

The Convention Management System is a comprehensive application that enables convention organizers to manage multi-day events with real-time occupancy tracking, attendance reporting, and role-based access control.

## Overview

This system provides a mobile-first Progressive Web App experience optimized for on-site convention management, allowing organizers to:

- Manage convention venues with hierarchical organization (Convention → Floor → Section)
- Track real-time section occupancy with visual indicators
- Collect attendance data in morning/afternoon periods
- Control access through four-tier role-based permissions
- Invite users via secure email with account activation
- Export complete convention data in multiple formats
- Search for available sections with accessibility filters

## Database Schema

### Core Tables

#### conventions
Stores convention details with date validation and location tracking.

```sql
CREATE TABLE conventions (
    id INTEGER PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(255) NOT NULL,
    country VARCHAR(255) NOT NULL,
    address TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    other_info TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT check_dates CHECK (end_date >= start_date)
);

-- Indexes
CREATE INDEX idx_conventions_location ON conventions(city, country);
CREATE INDEX idx_conventions_dates ON conventions(start_date, end_date);
```

**Key Features:**
- Date range validation (end_date must be >= start_date)
- Location-based indexing for conflict detection
- Optional address and additional information fields

#### floors
Organizes convention venues into physical levels.

```sql
CREATE TABLE floors (
    id INTEGER PRIMARY KEY,
    convention_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (convention_id) REFERENCES conventions(id) ON DELETE CASCADE
);

CREATE INDEX idx_floors_convention ON floors(convention_id);
```

#### sections
Defines seating areas with capacity and accessibility features.

```sql
CREATE TABLE sections (
    id INTEGER PRIMARY KEY,
    floor_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    number_of_seats INTEGER NOT NULL,
    occupancy INTEGER DEFAULT 0 CHECK (occupancy >= 0 AND occupancy <= 100),
    available_seats INTEGER DEFAULT 0 CHECK (available_seats >= 0),
    elder_friendly BOOLEAN DEFAULT FALSE,
    handicap_friendly BOOLEAN DEFAULT FALSE,
    information TEXT,
    last_occupancy_updated_by INTEGER,
    last_occupancy_updated_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE,
    FOREIGN KEY (last_occupancy_updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_sections_floor ON sections(floor_id);
CREATE INDEX idx_sections_occupancy ON sections(occupancy);
CREATE INDEX idx_sections_accessibility ON sections(elder_friendly, handicap_friendly);
```

**Key Features:**
- Real-time occupancy tracking (0-100%)
- Available seats counter
- Accessibility flags for elder-friendly and handicap-friendly sections
- Audit trail for occupancy updates

### Pivot Tables

#### convention_user
Links users to conventions they have access to.

```sql
CREATE TABLE convention_user (
    id INTEGER PRIMARY KEY,
    convention_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (convention_id) REFERENCES conventions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(convention_id, user_id)
);
```

#### convention_user_roles
Defines role-based permissions per convention.

```sql
CREATE TABLE convention_user_roles (
    id INTEGER PRIMARY KEY,
    convention_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role VARCHAR(50) NOT NULL CHECK (role IN ('Owner', 'ConventionUser', 'FloorUser', 'SectionUser')),
    created_at TIMESTAMP,
    FOREIGN KEY (convention_id) REFERENCES conventions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(convention_id, user_id, role)
);
```

#### floor_user & section_user
Scope user access to specific floors or sections.

```sql
CREATE TABLE floor_user (
    id INTEGER PRIMARY KEY,
    floor_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(floor_id, user_id)
);

CREATE TABLE section_user (
    id INTEGER PRIMARY KEY,
    section_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(section_id, user_id)
);
```

### Attendance Tracking

#### attendance_periods
Defines time-bound data collection periods.

```sql
CREATE TABLE attendance_periods (
    id INTEGER PRIMARY KEY,
    convention_id INTEGER NOT NULL,
    date DATE NOT NULL,
    period VARCHAR(20) NOT NULL CHECK (period IN ('morning', 'afternoon')),
    locked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (convention_id) REFERENCES conventions(id) ON DELETE CASCADE,
    UNIQUE(convention_id, date, period)
);
```

#### attendance_reports
Stores section attendance data per period.

```sql
CREATE TABLE attendance_reports (
    id INTEGER PRIMARY KEY,
    attendance_period_id INTEGER NOT NULL,
    section_id INTEGER NOT NULL,
    attendance INTEGER NOT NULL CHECK (attendance >= 0),
    reported_by INTEGER NOT NULL,
    reported_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (attendance_period_id) REFERENCES attendance_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(attendance_period_id, section_id)
);
```

## Role-Based Access Control

The system implements a four-tier hierarchical role system:

### Role Hierarchy

```
Owner (Full Control)
  ├─ All ConventionUser capabilities
  ├─ Delete convention
  ├─ Export convention data
  └─ Override all restrictions

ConventionUser (Convention-wide Access)
  ├─ View/edit all floors, sections, users
  ├─ Start/stop attendance reports
  ├─ Lock attendance periods
  └─ Manage all convention entities

FloorUser (Floor-scoped Access)
  ├─ View/edit assigned floors
  ├─ Manage sections on assigned floors
  ├─ View users on assigned floors
  └─ Report attendance for assigned sections

SectionUser (Section-scoped Access)
  ├─ View/edit assigned sections
  ├─ Update occupancy for assigned sections
  ├─ Report attendance for assigned sections
  └─ View users on assigned sections
```

### Permission Matrix

| Action | Owner | ConventionUser | FloorUser | SectionUser |
|--------|-------|----------------|-----------|-------------|
| View convention | ✓ | ✓ | ✓ | ✓ |
| Edit convention | ✓ | ✓ | ✗ | ✗ |
| Delete convention | ✓ | ✗ | ✗ | ✗ |
| Export data | ✓ | ✗ | ✗ | ✗ |
| Manage all floors | ✓ | ✓ | ✗ | ✗ |
| Manage assigned floors | ✓ | ✓ | ✓ | ✗ |
| Manage all sections | ✓ | ✓ | ✗ | ✗ |
| Manage assigned sections | ✓ | ✓ | ✓ | ✓ |
| Start/stop attendance | ✓ | ✓ | ✗ | ✗ |
| Report attendance | ✓ | ✓ | ✓ (assigned) | ✓ (assigned) |

## Key Features

### Occupancy Tracking

Real-time section occupancy with visual color coding:

- **0-25%**: Green (plenty of space)
- **26-50%**: Dark green (comfortable)
- **51-75%**: Yellow (filling up)
- **76-90%**: Orange (nearly full)
- **91-100%**: Red (full)

**Update Methods:**
1. Dropdown selection (0%, 10%, 25%, 50%, 75%, 100%)
2. "FULL" panic button (instant 100%)
3. Available seats input (auto-calculates percentage)

**Daily Reset:**
- Automated scheduler runs at 6:00 AM
- Resets all occupancy to 0%
- Clears available seats and update metadata

### Attendance Reporting

**Period Structure:**
- Two periods per day: morning and afternoon
- Maximum 2 reports per day per convention
- Periods can be locked to prevent further updates

**Workflow:**
1. ConventionUser starts attendance report
2. Section managers report attendance for their sections
3. System tracks "X of Y sections reported"
4. ConventionUser locks period when complete
5. Locked periods display historical data

**Restrictions:**
- Only original reporter can update before lock
- ConventionUser can override and lock anytime
- Locked periods are immutable

### User Invitation System

**Flow:**
1. Manager creates user with email and roles
2. System checks for existing email (deduplication)
3. Generates time-limited signed URL (24h expiration)
4. Sends invitation email via Mailgun
5. User sets password and confirms email
6. User gains access based on assigned roles

**Email Validation:**
- Globally unique email addresses
- Domain restriction: rejects "jwpub.org"
- Automatic confirmation email on email updates

### Data Export

**Supported Formats:**
- **Excel (.xlsx)**: Multi-sheet workbook with all data
- **Word (.docx)**: Formatted document with tables
- **Markdown (.md)**: Plain text with valid syntax

**Exported Data:**
- Convention details
- All floors and sections with capacity
- Complete attendance history
- User list with roles

### Section Search

**Filters:**
- Floor selection (optional)
- Elder-friendly sections
- Handicap-friendly sections
- Occupancy < 90% (automatic)

**Results:**
- Sorted by occupancy (ascending)
- Paginated for mobile
- No role-based filtering (available to all)

## Eloquent Models

### Convention Model

The `Convention` model represents a convention event with all its relationships and role-checking capabilities.

**Location:** `app/Models/Convention.php`

**Fillable Attributes:**
- `name` - Convention name
- `city` - City location
- `country` - Country location
- `address` - Optional full address
- `start_date` - Convention start date (cast to Carbon date)
- `end_date` - Convention end date (cast to Carbon date)
- `other_info` - Optional additional information

**Relationships:**

```php
// One-to-many relationships
$convention->floors()              // HasMany Floor
$convention->attendancePeriods()   // HasMany AttendancePeriod

// Many-to-many relationships
$convention->users()               // BelongsToMany User (via convention_user)
```

**Role Management Methods:**

```php
// Get all roles for a specific user
$roles = $convention->userRoles($user);
// Returns: Collection of role strings ['Owner', 'ConventionUser']

// Check if user has a specific role
$isOwner = $convention->hasRole($user, 'Owner');
// Returns: bool

// Check if user has any of the specified roles
$hasAccess = $convention->hasAnyRole($user, ['Owner', 'ConventionUser']);
// Returns: bool
```

**Usage Examples:**

```php
// Create a convention
$convention = Convention::create([
    'name' => 'Annual Tech Conference 2026',
    'city' => 'San Francisco',
    'country' => 'USA',
    'address' => '123 Convention Center Dr',
    'start_date' => '2026-06-15',
    'end_date' => '2026-06-17',
    'other_info' => 'Main auditorium available',
]);

// Attach user with roles
$convention->users()->attach($user->id);

// Check permissions
if ($convention->hasRole($user, 'Owner')) {
    // Allow deletion
}

if ($convention->hasAnyRole($user, ['Owner', 'ConventionUser'])) {
    // Allow full access
}

// Get user's roles
$userRoles = $convention->userRoles($user);
// ['Owner', 'ConventionUser']
```

**Date Casting:**

The model automatically casts `start_date` and `end_date` to Carbon instances, enabling date manipulation:

```php
$convention->start_date->format('F j, Y');  // "June 15, 2026"
$convention->end_date->diffInDays($convention->start_date);  // 2
```

## Business Logic Actions

### CreateConventionAction

The `CreateConventionAction` class encapsulates the business logic for creating a new convention with proper role assignment.

**Location:** `app/Actions/CreateConventionAction.php`

**Method Signature:**

```php
public function execute(array $data, User $creator): Convention
```

**Parameters:**
- `$data` - Validated convention data (name, city, country, dates, etc.)
- `$creator` - The user creating the convention

**Returns:** The newly created `Convention` instance with all relationships loaded

**Functionality:**

The action performs the following operations within a database transaction:

1. **Creates the convention** using the provided data
2. **Attaches the creator** to the convention via the `convention_user` pivot table
3. **Assigns roles** to the creator:
   - `Owner` role (full administrative privileges)
   - `ConventionUser` role (convention-wide access)
4. **Returns the fresh convention** instance with all relationships loaded

**Transaction Safety:**

All operations are wrapped in a database transaction to ensure data consistency. If any step fails, all changes are rolled back.

**Usage Example:**

```php
use App\Actions\CreateConventionAction;

// In ConventionController
public function store(StoreConventionRequest $request, CreateConventionAction $action)
{
    $convention = $action->execute(
        $request->validated(),
        $request->user()
    );
    
    return redirect()->route('conventions.show', $convention);
}
```

**Note on Attendance Periods:**

As per the design document, attendance periods are created lazily on first access rather than during convention creation. This optimizes initial convention setup and allows for flexible period management.

**Related:**
- See Task 4.1 in `tasks.md` for implementation details
- See Requirement 1.4 in `requirements.md` for role assignment specification

## Form Request Validation

### StoreConventionRequest

The `StoreConventionRequest` class handles validation for creating new conventions with comprehensive date overlap detection.

**Location:** `app/Http/Requests/StoreConventionRequest.php`

**Validation Rules:**

```php
[
    'name' => ['required', 'string', 'max:255'],
    'city' => ['required', 'string', 'max:255'],
    'country' => ['required', 'string', 'max:255'],
    'address' => ['nullable', 'string'],
    'start_date' => ['required', 'date', 'after_or_equal:today'],
    'end_date' => ['required', 'date', 'after_or_equal:start_date'],
    'other_info' => ['nullable', 'string'],
]
```

**Key Features:**

1. **Required Fields:** name, city, country, start_date, end_date
2. **Optional Fields:** address, other_info
3. **Date Validation:**
   - `start_date` must be today or in the future
   - `end_date` must be on or after `start_date`
4. **Overlap Detection:** Custom validation prevents creating conventions that overlap with existing ones in the same location

**Overlap Detection Logic:**

The request includes a custom validator that checks for overlapping conventions in the same city and country:

```php
private function hasOverlappingConvention(): bool
{
    return Convention::where('city', $this->city)
        ->where('country', $this->country)
        ->where(function ($query) {
            $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                ->orWhere(function ($q) {
                    $q->where('start_date', '<=', $this->start_date)
                      ->where('end_date', '>=', $this->end_date);
                });
        })
        ->exists();
}
```

**Overlap Scenarios Detected:**

1. **New convention starts during existing convention:**
   - Existing: June 1-5
   - New: June 3-7 ❌ (overlaps)

2. **New convention ends during existing convention:**
   - Existing: June 5-10
   - New: June 1-7 ❌ (overlaps)

3. **New convention completely contains existing convention:**
   - Existing: June 5-7
   - New: June 1-10 ❌ (overlaps)

4. **New convention is completely contained by existing convention:**
   - Existing: June 1-10
   - New: June 5-7 ❌ (overlaps)

**Error Message:**

When overlap is detected, the validation error is added to the `start_date` field:

```
"A convention already exists in this location during these dates."
```

**Usage Example:**

```php
// In ConventionController
public function store(StoreConventionRequest $request)
{
    // Validation automatically runs
    // If validation passes, create convention
    $convention = Convention::create($request->validated());
    
    // Attach creator as Owner and ConventionUser
    // ...
}
```

**Testing:**

The overlap detection is covered by property-based tests in Task 3.2 of the implementation plan.

## Implementation Status

The Convention Management System is currently under development. See `.kiro/specs/convention-management-system/` for:

- `requirements.md` - Complete requirements specification
- `design.md` - Technical design document
- `tasks.md` - Implementation task list

### Completed Tasks

- ✓ Task 1.1-1.7: All database migrations
- ✓ Task 2.1: Convention model with relationships and role management
- ✓ Task 3.1: StoreConventionRequest with overlap detection
- ✓ Task 4.1: CreateConventionAction with role assignment

### In Progress

See `tasks.md` for the complete implementation plan and current progress.

## Development Setup

### Database Seeding

The application includes a default test user for development:

```php
// database/seeders/DatabaseSeeder.php
User::factory()->create([
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@example.com',
]);
```

After running migrations, seed the database:

```bash
php artisan db:seed
```

This creates a test user account that can be used for development and testing. The user's password can be set via the invitation flow or manually in the database.

## Related Documentation

- [Architecture Overview](ARCHITECTURE.md) - Base application structure
- [Authentication](AUTHENTICATION.md) - User authentication system
- [Testing Guide](TESTING.md) - Testing conventions and examples
