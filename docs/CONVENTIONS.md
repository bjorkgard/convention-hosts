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

The system implements a four-tier hierarchical role system with middleware-enforced access control.

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

### Guest Convention Creation

Unauthenticated users can create a convention directly from the landing page without registering first.

**Route:** `POST /conventions/guest` (middleware: `guest`)

**Flow for existing users:**
1. Guest submits convention details along with their name, email, and mobile number
2. System finds the existing user by email
3. Convention is created via `CreateConventionAction` (assigns Owner and ConventionUser roles)
4. User is automatically logged in
5. Redirected to the new convention's detail page

**Flow for new users:**
1. Guest submits convention details along with their name, email, and mobile number
2. System creates a user account with a random password and `email_confirmed` set to false
3. Convention is created via `CreateConventionAction` (assigns Owner and ConventionUser roles)
4. A verification email is sent with a signed URL (24h expiry) to set a password
5. User is redirected to a confirmation page (not logged in) showing the convention name and email
6. User clicks the email link, sets a password, and is then logged in and redirected to the convention

**Automatic Cleanup:**

Guest conventions whose owner never confirms their email are automatically cleaned up after 7 days. A scheduled command (`app:cleanup-unconfirmed-guest-conventions`) runs daily at 3:00 AM and:

1. Finds users with `email_confirmed=false` created more than 7 days ago
2. Deletes all conventions where that user is the Owner (cascading deletes handle floors, sections, attendance, pivots)
3. If the user has no remaining conventions, deletes the user record as well

This prevents orphaned conventions from accumulating in the database.

**Validation:** Uses `StoreGuestConventionRequest` which validates user fields (first_name, last_name, email, mobile) and convention fields (name, city, country, start_date, end_date, address, other_info). Includes the same date overlap detection as authenticated convention creation.

**Controller:** `GuestConventionController@store`

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
3. Available seats input (calculates raw percentage, then snaps to the closest dropdown option)

**Daily Reset:**
- Automated scheduler runs at 6:00 AM
- Resets all occupancy to 0% and available seats to number_of_seats (all seats available)
- Clears update metadata (user and timestamp)

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

**Implementation:**

The export system uses a multi-sheet architecture for Excel exports via the `ConventionExport` class.

**Location:** `app/Exports/ConventionExport.php`

**Architecture:**

```php
class ConventionExport implements WithMultipleSheets
{
    public function __construct(protected Convention $convention)
    {
        // Eager load all related data for efficient export
        $this->convention->load([
            'floors.sections',
            'users',
            'attendancePeriods.reports.section.floor',
            'attendancePeriods.reports.reportedBy',
        ]);
    }

    public function sheets(): array
    {
        return [
            new ConventionSheet($this->convention),
            new FloorsAndSectionsSheet($this->convention),
            new AttendanceHistorySheet($this->convention),
            new UsersSheet($this->convention),
        ];
    }
}
```

**Excel Export Structure:**

The Excel export generates a workbook with four sheets:

1. **ConventionSheet** - Convention details (name, location, dates, additional info)
2. **FloorsAndSectionsSheet** - Complete venue hierarchy with capacity and occupancy data
3. **AttendanceHistorySheet** - All attendance periods and reports with timestamps
4. **UsersSheet** - User list with roles and contact information

**Performance Optimization:**

The constructor eagerly loads all related data using Eloquent's `load()` method to prevent N+1 query issues during export generation. This ensures efficient data retrieval even for large conventions with many floors, sections, and attendance records.

**Usage Example:**

```php
use App\Exports\ConventionExport;
use Maatwebsite\Excel\Facades\Excel;

// In ConventionController
public function export(Convention $convention, string $format)
{
    $this->authorize('export', $convention);
    
    return match($format) {
        'xlsx' => Excel::download(
            new ConventionExport($convention),
            "convention-{$convention->id}.xlsx"
        ),
        'docx' => (new ConventionWordExport($convention))->download(),
        'md' => (new ConventionMarkdownExport($convention))->download(),
    };
}
```

**Related Classes:**
- `ConventionSheet` - Convention details sheet
- `FloorsAndSectionsSheet` - Venue structure sheet
- `AttendanceHistorySheet` - Attendance data sheet
- `UsersSheet` - User information sheet
- `ConventionWordExport` - Word document export
- `ConventionMarkdownExport` - Markdown export

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

## Middleware and Authorization

### EnsureConventionAccess Middleware

The `EnsureConventionAccess` middleware enforces convention-level access control by verifying that authenticated users have at least one role for the requested convention.

**Location:** `app/Http/Middleware/EnsureConventionAccess.php`

**Purpose:** Prevents unauthorized access to convention resources by checking if the user has any role (Owner, ConventionUser, FloorUser, or SectionUser) for the requested convention.

**How It Works:**

1. Extracts the authenticated user from the request
2. Retrieves the convention from the route parameter
3. Checks if the user's conventions collection contains the requested convention
4. Aborts with 403 if user has no access
5. Allows request to proceed if user has any role for the convention

**Implementation:**

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    $convention = $request->route('convention');

    // Skip if no convention in route
    if (! $convention instanceof Convention) {
        return $next($request);
    }

    // Check if user has any role for this convention
    if (! $user->conventions->contains($convention)) {
        abort(403, 'No access to this convention');
    }

    return $next($request);
}
```

**Usage in Routes:**

```php
Route::middleware(['auth', EnsureConventionAccess::class])->group(function () {
    Route::get('/conventions/{convention}', [ConventionController::class, 'show']);
    Route::get('/conventions/{convention}/floors', [FloorController::class, 'index']);
    Route::get('/conventions/{convention}/sections', [SectionController::class, 'index']);
});
```

**Key Features:**

- **Graceful Skipping:** If no convention parameter exists in the route, the middleware passes through without checks
- **Relationship-Based:** Uses Eloquent relationships to verify access, leveraging the `convention_user` pivot table
- **Role-Agnostic:** Checks for any role without distinguishing between Owner, ConventionUser, FloorUser, or SectionUser
- **Clear Error Response:** Returns 403 Forbidden with descriptive message "No access to this convention" when access is denied

**Additional Authorization Layers:**

After the middleware confirms basic convention access, additional authorization is enforced through:

- **ScopeByRole Middleware:** Filters query results based on user's role scope (FloorUser sees only assigned floors, SectionUser sees only assigned sections)
- **EnsureOwnerRole Middleware:** Restricts certain actions (delete, export) to Owner role only
- **Policies:** Fine-grained permissions for Convention, Floor, Section, and User entities
- **Signed URLs:** Time-limited invitation links with cryptographic signatures

**Related:**
- See Task 6.1 in `tasks.md` for implementation details
- See Requirement 5.2 in `requirements.md` for role-based access specification
- See [Architecture Overview](ARCHITECTURE.md) for complete middleware documentation

## Controllers

### ConventionController

The `ConventionController` handles all convention CRUD operations, role-scoped data loading, and data export.

**Location:** `app/Http/Controllers/ConventionController.php`

**Endpoints:**

| Method | Action | Description | Authorization |
|--------|--------|-------------|---------------|
| `index()` | GET | List user's conventions (ordered by start_date desc) | Authenticated |
| `create()` | GET | Show convention creation form | Authenticated |
| `store()` | POST | Create convention via `CreateConventionAction` | Authenticated |
| `show()` | GET | Display convention with role-scoped floors, sections, attendance, users | Convention access |
| `update()` | PUT | Update convention details | Convention access |
| `destroy()` | DELETE | Delete convention | Owner only (policy) |
| `export()` | GET | Export convention data in specified format | Owner only (policy) |

**Role-Scoped Data Loading (show method):**

The `show()` method dynamically scopes data based on the user's role, using scoped IDs injected by the `ScopeByRole` middleware:

- **Owner / ConventionUser**: Sees all floors, sections, users, and attendance data
- **FloorUser**: Sees only assigned floors and their sections
- **SectionUser**: Sees only floors containing assigned sections, filtered to those sections

**Props returned to frontend:**

```php
[
    'convention' => $convention,
    'floors' => $floors,              // Role-scoped with sections
    'attendancePeriods' => $periods,  // With reports, sections, reporters
    'users' => $users,                // With roles for this convention
    'userRoles' => $userRoles,        // Current user's roles
]
```

**Export:**

The `export()` method delegates to `ExportConventionAction` and returns a downloadable file that is automatically deleted after sending.

### GuestConventionController

The `GuestConventionController` allows unauthenticated users to create a convention without registering first.

**Location:** `app/Http/Controllers/GuestConventionController.php`

**Endpoints:**

| Method | Action | Description | Authorization |
|--------|--------|-------------|---------------|
| `store()` | POST | Create convention as guest, find/create user | Guest only |

**Behavior:**

1. Validates user fields (first_name, last_name, email) and convention fields via `StoreGuestConventionRequest`
2. Finds an existing user by email or creates a new one with a random password and `email_confirmed=false`
3. Delegates convention creation to `CreateConventionAction` (assigns Owner + ConventionUser roles)
4. **Existing user:** Logs the user in via `Auth::login()` and redirects to the convention detail page
5. **New user:** Sends a `GuestConventionVerification` email with a signed URL (24h expiry), then renders the confirmation page without logging the user in

## TypeScript Interfaces

The frontend data models are defined in `resources/js/types/convention.ts` and mirror the Eloquent models:

| Interface | Description | Key Fields |
|-----------|-------------|------------|
| `Convention` | Convention event | name, city, country, start_date, end_date |
| `Floor` | Venue level | convention_id, name |
| `Section` | Seating area | floor_id, number_of_seats, occupancy, available_seats, elder_friendly, handicap_friendly |
| `AttendancePeriod` | Reporting period | convention_id, date, period (`'morning'` \| `'afternoon'`), locked |
| `AttendanceReport` | Section attendance | attendance_period_id, section_id, attendance, reported_by |

All interfaces include optional relationship fields (e.g., `floors?: Floor[]` on Convention) for when data is eagerly loaded via Inertia props. The `User` type is imported from `@/types/auth`.

## Frontend Hooks

### useConventionRole

Reads the current user's roles and scope from Inertia shared page props.

**Location:** `resources/js/hooks/use-convention-role.ts`

**Expected Page Props:**

| Prop | Type | Description |
|------|------|-------------|
| `userRoles` | `Role[]` | Roles for the current convention |
| `userFloorIds` | `number[]` | Floor IDs the user is assigned to |
| `userSectionIds` | `number[]` | Section IDs the user is assigned to |

These props are provided by the `ConventionController.show()` method.

**Return Value:**

```typescript
interface UseConventionRoleReturn {
    isOwner: boolean;
    isConventionUser: boolean;
    isFloorUser: boolean;
    isSectionUser: boolean;
    hasFloorAccess: (floorId: number) => boolean;
    hasSectionAccess: (sectionId: number) => boolean;
}
```

**Usage:**

```tsx
import { useConventionRole } from '@/hooks/use-convention-role';

function FloorList({ floors }) {
    const { isOwner, isConventionUser, hasFloorAccess } = useConventionRole();

    return floors
        .filter((floor) => hasFloorAccess(floor.id))
        .map((floor) => (
            <FloorRow
                key={floor.id}
                floor={floor}
                canEdit={isOwner || isConventionUser}
            />
        ));
}
```

Owner and ConventionUser roles automatically have access to all floors and sections. FloorUser and SectionUser access is determined by the scoped ID sets.

## Frontend Navigation

### Convention Sidebar Navigation

When viewing a convention, the sidebar displays context-aware navigation links scoped to the current convention. The `NavConvention` component reads the convention from Inertia page props and renders role-appropriate links.

**Location:** `resources/js/components/nav-convention.tsx`

**Displayed Links:**

| Link | Icon | Visible To |
|------|------|------------|
| Floors | Building2 | Owner, ConventionUser, FloorUser |
| Sections | Grid3X3 | All convention users |
| Users | Users | Owner, ConventionUser, FloorUser |
| Search | Search | All convention users |

The component uses the `useConventionRole` hook for role checks and Wayfinder type-safe actions for URL generation. It only renders when a `convention` prop is present in the page props.

**Integration:**

The `NavConvention` component is rendered in the `AppSidebar` below the main navigation. It automatically appears on convention detail pages and hides on non-convention pages.

```tsx
<SidebarContent>
    <NavMain items={mainNavItems} />
    <NavConvention />
</SidebarContent>
```

## Implementation Status

The Convention Management System is currently under development.

### Completed

- Database migrations (conventions, floors, sections, users, pivots, attendance)
- Eloquent models with relationships and role management
- Form request validation (conventions, floors, sections, users, attendance, search, passwords)
- Business logic actions (CreateConvention, InviteUser, UpdateOccupancy, ExportConvention, AttendanceReportService)
- Export system (Excel, Word, Markdown)
- Middleware and authorization (EnsureConventionAccess, EnsureOwnerRole, ScopeByRole, policies)
- Controllers and routes (Convention, Floor, Section, User, Attendance, Search, Invitation)
- Scheduled tasks (daily occupancy reset via `app:reset-daily-occupancy` command)
- Property-based tests for core business rules

### In Progress

- Email system (Mailgun integration, invitation and confirmation mailables)
- Frontend UI components and Inertia pages
- PWA support

### Recently Added

- **Section CRUD from FloorsIndex** — Full section create/edit/delete management from the Floors page via modal dialogs. Includes:
  - **`SectionModal` component** (`resources/js/components/conventions/section-modal.tsx`) — Dialog for creating and editing sections with floor selector dropdown, accessibility checkboxes, and inline validation errors. Uses `useForm` from Inertia with Wayfinder type-safe routing.
  - **`FloorRow` section action buttons** — Inline edit (Pencil) and delete (Trash2) icon buttons next to each section in expanded floor rows. Visibility is role-gated: Owner, ConventionUser, and assigned FloorUser can edit/delete; SectionUser sees no action buttons. Props: `onEditSection`, `onDeleteSection`, `userFloorIds`, `userSectionIds`.
  - **`UpdateSectionRequest`** (`app/Http/Requests/UpdateSectionRequest.php`) — Dedicated form request for section updates (no `floor_id` since sections don't change floors on edit).
  - **`StoreSectionRequest` updated** — Added `floor_id` validation (`sometimes|required|exists:floors,id`) for creating sections from the FloorsIndex page.
  - **`SectionController` updated** — Store/update/destroy actions redirect to `floors.index` route. Store accepts `floor_id` from request body. Update uses `UpdateSectionRequest`.
  - **Property-based tests** — Comprehensive PBT coverage for creation, update, deletion, cancellation, authorization enforcement, and server-side validation rejection.
- **`GuestConventionController`** (`app/Http/Controllers/GuestConventionController.php`) — Allows unauthenticated users to create a convention from the landing page. Finds or creates a user by email, creates the convention via `CreateConventionAction`, and logs the user in automatically. Route: `POST /conventions/guest` (guest middleware)
- **`StoreGuestConventionRequest`** (`app/Http/Requests/StoreGuestConventionRequest.php`) — Form request validating both user fields (first_name, last_name, email) and convention fields with date overlap detection
- **`NavConvention` component** (`resources/js/components/nav-convention.tsx`) — Context-aware sidebar navigation that displays convention-specific links (Floors, Sections, Users, Search) with role-based visibility using `useConventionRole` and Wayfinder type-safe routing
- **`useConventionRole` hook** (`resources/js/hooks/use-convention-role.ts`) — React hook that reads role and scope data from Inertia page props, exposing `isOwner`, `isConventionUser`, `isFloorUser`, `isSectionUser` booleans and `hasFloorAccess(floorId)` / `hasSectionAccess(sectionId)` helpers
- **TypeScript type definitions** (`resources/js/types/convention.ts`) for all convention data models: `Convention`, `Floor`, `Section`, `AttendancePeriod`, `AttendanceReport` with full relationship typing and optional nested includes

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
