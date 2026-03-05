# Project Structure

## Backend (Laravel)

### Application Code (`app/`)
- `Actions/` - Business logic actions (CreateConventionAction, ExportConventionAction, InviteUserAction, UpdateOccupancyAction, Fortify/)
- `Concerns/` - Reusable traits (validation rules for password, profile)
- `Console/Commands/` - Artisan commands (ResetDailyOccupancy)
- `Exports/` - Data export classes (ConventionExport, ConventionWordExport, ConventionMarkdownExport, sheets for attendance/floors/users)
- `Http/Controllers/` - HTTP controllers organized by feature (Auth/, Settings/)
- `Http/Middleware/` - Custom middleware (EnsureConventionAccess, EnsureOwnerRole, HandleAppearance, HandleInertiaRequests, ScopeByRole)
- `Http/Requests/` - Form request validation classes organized by feature (Settings/, plus convention/floor/section/user/occupancy/attendance/search requests)
- `Models/` - Eloquent models:
  - `User.php` - User model with convention relationships
  - `Convention.php` - Convention model with floors and attendance periods
  - `Floor.php` - Floor model with sections
  - `Section.php` - Section model with occupancy tracking
  - `AttendancePeriod.php` - Time-bound attendance reporting periods
  - `AttendanceReport.php` - Section attendance data
- `Providers/` - Service providers (AppServiceProvider, FortifyServiceProvider)

### Configuration (`config/`)
Standard Laravel configuration files including `fortify.php` for authentication settings.

### Database (`database/`)
- `factories/` - Model factories for testing (User, Convention, Floor, Section, AttendancePeriod, AttendanceReport)
- `migrations/` - Database schema migrations:
  - Convention management tables (conventions, floors, sections)
  - User pivot tables (convention_user, floor_user, section_user)
  - Role management (convention_user_roles)
  - Attendance tracking (attendance_periods, attendance_reports)
- `seeders/` - Database seeders
- `database.sqlite` - SQLite database file (development)

### Routes
- `routes/web.php` - Main web routes
- `routes/settings.php` - Settings-related routes (included in web.php)
- `routes/console.php` - Artisan console commands

## Frontend (React + TypeScript)

### Resources (`resources/js/`)

- `actions/` - **Auto-generated** Wayfinder type-safe route actions (DO NOT EDIT)
  - Mirrors backend controller structure
  - Provides type-safe routing from frontend to backend
  
- `components/` - React components
  - `ui/` - Reusable UI components (shadcn/ui style, DO NOT EDIT)
  - `conventions/` - Convention feature components (attendance-report-banner, available-seats-input, convention-card, export-dropdown, floor-row, full-button, occupancy-dropdown, occupancy-indicator, role-badge, section-card, user-row)
  - General components (app-shell, app-sidebar, nav-main, breadcrumbs, heading, input-error, etc.)
  
- `hooks/` - Custom React hooks
  - `use-appearance.tsx` - Theme/appearance management
  - `use-attendance-report.ts` - Attendance report state management
  - `use-clipboard.ts` - Clipboard copy utility
  - `use-convention-role.ts` - Convention role checks
  - `use-current-url.ts` - Current URL tracking
  - `use-initials.tsx` - User initials generation
  - `use-mobile-navigation.ts` - Mobile navigation state
  - `use-mobile.tsx` - Mobile detection
  - `use-occupancy-color.ts` - Occupancy color mapping
  - `use-two-factor-auth.ts` - 2FA state management
  
- `layouts/` - Page layout components
  - `app/` - Authenticated app layout configuration
  - `auth/` - Authentication pages layout configuration
  - `settings/` - Settings pages layout configuration
  - `app-layout.tsx` - Main app layout wrapper
  - `auth-layout.tsx` - Auth pages layout wrapper
  
- `lib/` - Utility functions
  - `utils.ts` - Common utilities (cn for class merging)
  
- `pages/` - Inertia.js page components
  - `auth/` - Authentication pages (login, register, confirm-password, forgot-password, reset-password, two-factor-challenge, verify-email)
  - `settings/` - Settings pages (profile, password, two-factor, appearance)
  - `dashboard.tsx` - Main dashboard
  - `welcome.tsx` - Landing page
  - `conventions/index.tsx` - Convention listing with grid layout and empty state
  - Note: Remaining convention pages (create, show) and floor/section/user/search pages are planned but not yet implemented as page components.
  
- `routes/` - **Auto-generated** Wayfinder route definitions (DO NOT EDIT)
  
- `types/` - TypeScript type definitions
  - `index.ts` - Barrel export with shared PageProps, Flash, Errors types
  - `auth.ts` - Authentication types
  - `convention.ts` - Convention, Floor, Section, Attendance types
  - `navigation.ts` - Navigation types
  - `ui.ts` - UI component types
  - `user.ts` - User and Role types
  - `global.d.ts` - Global type declarations
  - `vite-env.d.ts` - Vite client type references
  
- `wayfinder/` - Wayfinder configuration

### Entry Points
- `app.tsx` - Main client-side entry point
- `ssr.tsx` - Server-side rendering entry point

## Styling (`resources/css/`)
- `app.css` - Main Tailwind CSS entry point

## Public Assets (`public/`)
- `build/` - Compiled frontend assets (auto-generated)
- Static assets (favicon, icons, etc.)

## Key Conventions

### Backend
- Controllers are organized by feature in subdirectories
- Form requests handle validation logic
- Actions contain reusable business logic
- Concerns provide shared traits

### Frontend
- Components use TypeScript with strict typing
- Wayfinder provides type-safe routing (actions/ and routes/ are auto-generated)
- UI components follow shadcn/ui patterns
- Hooks encapsulate reusable stateful logic
- Pages map 1:1 with Inertia routes

### Auto-Generated Files (DO NOT EDIT)
- `resources/js/actions/**` - Generated by Wayfinder
- `resources/js/routes/**` - Generated by Wayfinder
- `resources/js/components/ui/*` - Generated by shadcn/ui CLI
- `public/build/**` - Generated by Vite

### Code Style
- PHP: Laravel Pint with Laravel preset
- TypeScript/React: ESLint + Prettier
- Import ordering: alphabetical with grouped categories
- Consistent type imports: `import type { ... }`
