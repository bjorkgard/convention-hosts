# Architecture Overview

This document provides a comprehensive overview of the Convention Management System architecture, design patterns, and conventions.

## Technology Stack

### Backend Stack

- **PHP 8.2+** - Modern PHP with typed properties, attributes, and enums
- **Laravel 12** - Latest Laravel framework with improved performance
- **Laravel Fortify** - Authentication backend without UI
- **Laravel Wayfinder** - Type-safe routing between backend and frontend
- **Inertia.js** - Glues Laravel and React together
- **SQLite** - Default database for development

### Frontend Stack

- **React 19** - Latest React with compiler optimizations
- **TypeScript** - Full type safety across the frontend
- **Vite** - Fast build tool with HMR
- **Tailwind CSS 4** - Utility-first CSS framework
- **Radix UI** - Accessible component primitives
- **Headless UI** - Additional unstyled components
- **Lucide React** - Icon library

## Project Structure

```
laravel-react-starter-kit/
├── app/                           # Laravel Application
│   ├── Actions/                   # Business Logic Actions
│   │   └── Fortify/              # Fortify action implementations
│   ├── Concerns/                  # Reusable Traits
│   ├── Http/
│   │   ├── Controllers/          # HTTP Controllers
│   │   │   └── Settings/         # Settings feature controllers
│   │   ├── Middleware/           # Custom Middleware
│   │   ├── Requests/             # Form Request Validation
│   │   │   └── Settings/         # Settings feature requests
│   │   └── Responses/            # Custom Fortify Responses (LoginResponse)
│   ├── Models/                    # Eloquent Models
│   └── Providers/                 # Service Providers
│
├── bootstrap/                     # Laravel Bootstrap
│   ├── app.php                   # Application bootstrap
│   ├── cache/                    # Framework cache
│   └── providers.php             # Provider registration
│
├── config/                        # Configuration Files
│   ├── app.php                   # Application config
│   ├── auth.php                  # Authentication config
│   ├── fortify.php               # Fortify config
│   └── inertia.php               # Inertia config
│
├── database/                      # Database Files
│   ├── factories/                # Model Factories
│   ├── migrations/               # Database Migrations
│   └── seeders/                  # Database Seeders
│
├── public/                        # Public Assets
│   ├── build/                    # Compiled Assets (generated)
│   └── index.php                 # Application Entry Point
│
├── resources/                     # Frontend Resources
│   ├── css/
│   │   └── app.css               # Tailwind Entry Point
│   └── js/
│       ├── actions/              # Wayfinder Actions (generated)
│       ├── components/           # React Components
│       │   ├── ui/               # Base UI Components
│       │   └── ...               # Feature Components
│       ├── hooks/                # Custom React Hooks
│       ├── layouts/              # Layout Components
│       ├── lib/                  # Utility Functions
│       ├── pages/                # Inertia Pages
│       │   ├── auth/             # Authentication Pages
│       │   ├── conventions/      # Convention Pages
│       │   ├── sections/         # Section Pages
│       │   ├── settings/         # Settings Pages
│       │   └── welcome.tsx       # Landing Page
│       ├── routes/               # Wayfinder Routes (generated)
│       ├── types/                # TypeScript Types
│       ├── app.tsx               # Client Entry Point
│       └── ssr.tsx               # SSR Entry Point
│
├── routes/                        # Route Definitions
│   ├── web.php                   # Web Routes
│   ├── settings.php              # Settings Routes
│   └── console.php               # Console Commands
│
├── storage/                       # Storage Directory
│   ├── app/                      # Application Storage
│   ├── framework/                # Framework Storage
│   └── logs/                     # Application Logs
│
└── tests/                         # Tests
    ├── Feature/                  # Feature Tests
    └── Unit/                     # Unit Tests
```

## Architecture Patterns

### Backend Architecture

#### MVC Pattern

The application follows Laravel's MVC pattern:

- **Models** (`app/Models/`) - Data layer, Eloquent ORM
- **Views** - Replaced by Inertia.js React components
- **Controllers** (`app/Http/Controllers/`) - Handle HTTP requests

#### Action Pattern

Complex business logic is extracted into Action classes:

```php
// app/Actions/Fortify/CreateNewUser.php
class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        // Validation and user creation logic
    }
}
```

Benefits:
- Single responsibility
- Reusable across controllers
- Easier to test

#### Controller Pattern

Controllers handle HTTP requests and delegate to Actions for business logic:

```php
// app/Http/Controllers/ConventionController.php
class ConventionController extends Controller
{
    public function store(StoreConventionRequest $request, CreateConventionAction $action)
    {
        $convention = $action->execute($request->validated(), $request->user());
        return redirect()->route('conventions.show', $convention);
    }
}
```

Controllers use dependency injection for Actions and Form Requests, keeping methods thin and focused on HTTP concerns.

#### Form Request Validation

Validation logic is encapsulated in Form Request classes:

```php
// app/Http/Requests/Settings/ProfileUpdateRequest.php
class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
```

#### Service Provider Pattern

Application bootstrapping and service registration:

```php
// app/Providers/FortifyServiceProvider.php
class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        // Configure Fortify features
    }
}
```

### Frontend Architecture

#### Component-Based Architecture

React components are organized by feature and reusability:

```
components/
├── ui/              # Base components (buttons, inputs, etc.)
├── app-shell.tsx    # Application shell
├── nav-main.tsx     # Main navigation
├── nav-convention.tsx # Convention-scoped navigation (role-aware)
└── ...              # Feature-specific components
```

#### Page Components

Inertia pages map 1:1 with backend routes:

```tsx
// resources/js/pages/conventions/index.tsx
export default function ConventionsIndex() {
    return (
        <AppLayout>
            <h1>My Conventions</h1>
        </AppLayout>
    );
}
```

#### Layout Components

Layouts provide consistent structure:

```tsx
// resources/js/layouts/app-layout.tsx
export function AppLayout({ children }: PropsWithChildren) {
    return (
        <div>
            <Navigation />
            <main>{children}</main>
        </div>
    );
}
```

#### Custom Hooks

Reusable stateful logic:

```tsx
// resources/js/hooks/use-appearance.tsx
export function useAppearance() {
    const [theme, setTheme] = useState<'light' | 'dark'>('light');
    // Theme management logic
    return { theme, setTheme };
}
```

## Data Flow

### Request Flow

1. **User Action** - User interacts with React component
2. **Inertia Request** - Component makes Inertia request
3. **Laravel Route** - Request hits Laravel route
4. **Controller** - Controller processes request
5. **Action/Model** - Business logic executed
6. **Inertia Response** - Controller returns Inertia response
7. **React Update** - React component receives props and re-renders

### Type-Safe Routing with Wayfinder

```tsx
// Frontend - Type-safe route generation
import { route } from '@/routes';

// TypeScript knows available routes and parameters
<Link href={route('settings.profile')}>Profile</Link>
```

```php
// Backend - Route definition
Route::get('/settings/profile', [ProfileController::class, 'edit'])
    ->name('settings.profile');
```

Wayfinder generates TypeScript definitions from Laravel routes, ensuring type safety.

## Authentication System

### Fortify Integration

Laravel Fortify provides the authentication backend:

- User registration
- Login/logout
- Password reset
- Email verification
- Two-factor authentication

### Authentication Flow

1. **Frontend Form** - User submits credentials
2. **Inertia POST** - Form data sent to Fortify endpoint
3. **Fortify Action** - Fortify processes authentication
4. **Session Created** - Laravel session established
5. **Redirect** - User redirected to conventions list
6. **Authenticated State** - React receives authenticated user props

### Middleware Protection

Routes are protected using Laravel middleware:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/conventions', [ConventionController::class, 'index']);
});
```

### Custom Middleware

#### EnsureConventionAccess

Verifies that the authenticated user has access to a convention before allowing the request to proceed:

```php
// app/Http/Middleware/EnsureConventionAccess.php
class EnsureConventionAccess
{
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
}
```

**Usage:**
```php
Route::middleware(['auth', EnsureConventionAccess::class])->group(function () {
    Route::get('/conventions/{convention}', [ConventionController::class, 'show']);
});
```

This middleware ensures that users can only access conventions they are associated with through the role-based access control system.

## State Management

### Server State (Inertia)

Inertia manages server state through props:

```tsx
interface ConventionsPageProps {
    conventions: Convention[];
}

export default function ConventionsIndex({ conventions }: ConventionsPageProps) {
    // Props are automatically typed and reactive
}
```

### Client State (React)

Local component state using React hooks:

```tsx
const [isOpen, setIsOpen] = useState(false);
```

### Shared State (Context)

Global client state using React Context:

```tsx
// resources/js/hooks/use-appearance.tsx
const AppearanceContext = createContext<AppearanceContext | undefined>(undefined);

export function AppearanceProvider({ children }: PropsWithChildren) {
    // Shared theme state
}
```

## Database Design

### Users Table

```sql
users
├── id
├── name
├── email
├── email_verified_at
├── password
├── two_factor_secret
├── two_factor_recovery_codes
├── two_factor_confirmed_at
├── remember_token
├── created_at
└── updated_at
```

### Sessions Table

Laravel stores sessions in the database for better scalability.

## Security Considerations

### CSRF Protection

All POST requests include CSRF token automatically via Inertia.

### Password Hashing

Passwords are hashed using bcrypt with configurable rounds:

```env
BCRYPT_ROUNDS=12
```

### Two-Factor Authentication

TOTP-based 2FA with recovery codes for account recovery.

### Input Validation

All user input is validated using Form Requests before processing.

### SQL Injection Prevention

Eloquent ORM uses prepared statements, preventing SQL injection.

## Performance Optimizations

### Frontend

- **Code Splitting** - Automatic route-based code splitting
- **React Compiler** - Automatic memoization
- **Vite** - Fast HMR and optimized builds
- **Lazy Loading** - Components loaded on demand

### Backend

- **Query Optimization** - Eager loading to prevent N+1 queries
- **Caching** - Configuration, routes, and views cached in production
- **Queue System** - Background job processing
- **Database Indexing** - Proper indexes on frequently queried columns

## Testing Strategy

### Backend Tests (Pest)

```php
test('user can update profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/profile', [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $user->email,
        ])
        ->assertRedirect();

    expect($user->fresh()->first_name)->toBe('New');
    expect($user->fresh()->last_name)->toBe('Name');
});
```

### Frontend Tests

TypeScript provides compile-time type checking, catching many errors before runtime.

## Deployment Architecture

### Production Stack

- **Web Server** - Nginx or Apache
- **PHP** - PHP-FPM
- **Database** - MySQL or PostgreSQL
- **Cache** - Redis
- **Queue** - Redis or database
- **Assets** - CDN for static assets

See [Deployment Guide](DEPLOYMENT.md) for detailed deployment instructions.

## Conventions

### Naming Conventions

- **Controllers** - Singular, `ProfileController`
- **Models** - Singular, `User`
- **Tables** - Plural, `users`
- **Routes** - Kebab-case, `settings.two-factor`
- **Components** - PascalCase, `AppShell`
- **Files** - Kebab-case, `app-shell.tsx`

### Code Organization

- Group by feature, not by type
- Keep related files close together
- Use index files for clean imports
- Separate concerns (UI, logic, types)

### Type Safety

- Use TypeScript strict mode
- Define interfaces for all props
- Avoid `any` type
- Use Wayfinder for route type safety

## Architectural Decision Records

This section documents the key design decisions made for the Convention Management System and the reasoning behind them.

### ADR-1: Role-Based Access Control Design

**Context:** The system needs to support multiple permission levels within a single convention, from full administrative control down to single-section management.

**Decision:** Implement a four-tier role system (Owner, ConventionUser, FloorUser, SectionUser) using pivot tables rather than a general-purpose permissions library like Spatie.

**Implementation:**
- `convention_user` pivot links users to conventions
- `convention_user_roles` pivot stores per-convention roles with a unique constraint on (convention_id, user_id, role)
- `floor_user` and `section_user` pivots scope FloorUser and SectionUser access
- Three middleware layers enforce access: `EnsureConventionAccess` (any role), `EnsureOwnerRole` (owner only), `ScopeByRole` (filters queries by role scope)
- Laravel Policies provide fine-grained action-level authorization on Convention, Floor, Section, and User models

**Rationale:** A dedicated pivot-based approach is simpler and more performant than a generic permissions system for this fixed set of four roles. The middleware stack provides defense-in-depth: first verifying convention membership, then scoping data visibility, then checking action-level permissions via policies. Users can hold multiple roles simultaneously (e.g., Owner + ConventionUser), and the hierarchy is implicit rather than stored — Owner inherits all ConventionUser capabilities by convention in the policy logic.

### ADR-2: Occupancy Tracking Approach

**Context:** Section managers need to update occupancy quickly from mobile devices during live events. Updates must be immediate and intuitive.

**Decision:** Provide three complementary update methods that all resolve to the same underlying data: a percentage dropdown (0/10/25/50/75/100%), a "FULL" panic button, and a numeric available-seats input.

**Implementation:**
- `UpdateOccupancyAction` normalizes all three input types into both `occupancy` (percentage) and `available_seats` (integer) fields on the section
- Dropdown and FULL button set occupancy directly; available-seats input calculates occupancy as `100 - ((available_seats / number_of_seats) * 100)`
- Every update records `last_occupancy_updated_by` and `last_occupancy_updated_at` for audit
- A scheduled command (`ResetDailyOccupancy`) resets all sections to 0% at 6:00 AM daily
- Color coding (green → dark-green → yellow → orange → red) is computed client-side via the `useOccupancyColor` hook
- `OccupancyGauge` component renders a semi-circle SVG gauge with color-mapped fill and percentage label, reusing `getOccupancyLevel` from the same hook

**Rationale:** Predefined percentage steps are fastest for mobile use during busy events. The FULL button handles the most urgent case (section at capacity) with a single tap. Available-seats input provides precision when exact counts are known. Storing both percentage and seat count avoids repeated calculation and keeps the search query simple (filter on `occupancy < 90`). Daily reset ensures each convention day starts fresh without manual intervention.

### ADR-3: Attendance Reporting Flow

**Context:** Convention managers need to collect attendance counts from section managers across the venue, with clear start/stop boundaries and historical immutability.

**Decision:** Use a two-phase model with explicit period creation and locking. Attendance is collected per-section within time-bound periods (morning/afternoon), and locked periods become immutable records.

**Implementation:**
- `AttendancePeriod` model with `locked` boolean and unique constraint on (convention_id, date, period)
- `AttendanceReport` model with unique constraint on (attendance_period_id, section_id) — one report per section per period
- `AttendanceReportService` manages the lifecycle: `startReport()` creates/retrieves a period (max 2 per day), `reportAttendance()` creates/updates individual reports, `stopReport()` locks the period
- Before locking, only the original reporter can update their section's attendance. After locking, no updates are allowed
- ConventionUser role can lock periods even when not all sections have reported

**Rationale:** Explicit start/stop boundaries give convention managers clear control over data collection windows. The max-2-per-day limit maps naturally to morning and afternoon sessions. Locking provides data integrity for historical reporting — once a period is locked, the attendance numbers are final. The reporter-only update restriction prevents accidental overwrites during active collection, while the ConventionUser override ensures periods can always be finalized.

### ADR-4: Progressive Web App Implementation

**Context:** The application is primarily used on-site at conventions from mobile devices. Users need quick access without app store installation.

**Decision:** Implement PWA with a Web App Manifest and a basic service worker using a cache-first strategy for the app shell.

**Implementation:**
- `public/manifest.json` defines the app metadata, icons (72px–512px), and display mode (standalone)
- `public/sw.js` implements install/fetch events with cache-first for static assets
- Service worker registration in `app.blade.php` with feature detection
- `InstallPrompt` React component listens for `beforeinstallprompt` and provides platform-specific installation instructions (iOS Safari, Android Chrome)
- Meta tags for `theme-color` and `apple-touch-icon` in the HTML head

**Rationale:** A full native app would add significant development and distribution overhead for what is essentially a responsive web application. PWA provides the key benefits — home screen icon, full-screen display, offline shell caching — with zero app store friction. The cache-first strategy ensures the app shell loads quickly even on spotty venue Wi-Fi, while data requests always go to the network for real-time accuracy.

### ADR-5: Export System Design

**Context:** Convention owners need to export complete convention data for external analysis, archiving, and sharing with stakeholders who may not have system access.

**Decision:** Support three export formats (.xlsx, .docx, .md) using dedicated export classes with eager-loaded data to prevent N+1 queries.

**Implementation:**
- `ExportConventionAction` delegates to format-specific exporters based on the requested format
- `ConventionExport` (Excel) implements `WithMultipleSheets` from maatwebsite/excel with four sheets: Convention details, Floors & Sections, Attendance History, Users
- `ConventionWordExport` uses phpoffice/phpword to generate structured .docx documents with tables
- `ConventionMarkdownExport` generates plain Markdown using PHP string building
- All exporters eager-load the full relationship tree (`floors.sections`, `users`, `attendancePeriods.reports`) in the constructor to batch all queries upfront

**Rationale:** Three formats cover the main use cases: Excel for data analysis and filtering, Word for formal reports and printing, Markdown for lightweight sharing and version control. The multi-sheet Excel approach keeps related data organized without overwhelming a single sheet. Eager loading all relationships once (rather than lazy-loading during serialization) keeps export performance predictable regardless of convention size. The export action is restricted to the Owner role since it exposes the complete convention dataset.

## Next Steps

- Learn about [Type-Safe Routing](ROUTING.md)
- Understand [Authentication System](AUTHENTICATION.md)
- Read [Development Guide](DEVELOPMENT.md)
- Review [Testing Guide](TESTING.md)
