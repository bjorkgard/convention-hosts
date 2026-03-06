# Project Structure

## Backend (Laravel)

### Application Code (`app/`)
- `Actions/` - Business logic actions (CreateConventionAction, ExportConventionAction, InviteUserAction, UpdateOccupancyAction, Fortify/)
- `Concerns/` - Reusable traits (PasswordValidationRules, ProfileValidationRules, SanitizesInput)
- `Console/Commands/` - Artisan commands (ResetDailyOccupancy)
- `Exports/` - Data export classes (ConventionExport, ConventionWordExport, ConventionMarkdownExport, sheets for attendance/floors/users/convention)
- `Http/Controllers/` - HTTP controllers organized by feature (Auth/, Settings/, VersionController)
- `Http/Middleware/` - Custom middleware (EnsureConventionAccess, EnsureOwnerRole, HandleAppearance, HandleInertiaRequests, ScopeByRole, SecureHeaders)
- `Http/Requests/` - Form request validation classes organized by feature (Settings/, plus Store/Update requests for convention, floor, section, user, occupancy, attendance, search, guest convention, set password)
- `Listeners/` - Event listeners (SecurityEventListener for failed login, authorization failure, invalid signed URL, rate limit logging)
- `Mail/` - Mailable classes (UserInvitation, EmailConfirmation, GuestConventionVerification)
- `Models/` - Eloquent models:
  - `User.php` - User model with convention relationships
  - `Convention.php` - Convention model with floors and attendance periods
  - `Floor.php` - Floor model with sections
  - `Section.php` - Section model with occupancy tracking
  - `AttendancePeriod.php` - Time-bound attendance reporting periods
  - `AttendanceReport.php` - Section attendance data
- `Observers/` - Model observers (UserObserver - resets email_confirmed on email change, sends confirmation email)
- `Policies/` - Authorization policies (ConventionPolicy, FloorPolicy, SectionPolicy, UserPolicy)
- `Providers/` - Service providers (AppServiceProvider, FortifyServiceProvider)
- `Services/` - Business logic services (AttendanceReportService - start/stop reports, report attendance, period determination)

### Configuration (`config/`)
Standard Laravel configuration files including `fortify.php` for authentication settings.

### Database (`database/`)
- `factories/` - Model factories for testing (User, Convention, Floor, Section, AttendancePeriod, AttendanceReport)
- `migrations/` - Database schema migrations:
  - Convention management tables (conventions, floors, sections)
  - User pivot tables (convention_user, floor_user, section_user)
  - Role management (convention_user_roles)
  - Attendance tracking (attendance_periods, attendance_reports)
  - Two-factor authentication columns
- `seeders/` - Database seeders (DatabaseSeeder, DemoSeeder with sample convention data and all role types)
- `database.sqlite` - SQLite database file (development)

### Routes
- `routes/web.php` - Main web routes (conventions, floors, sections, users, attendance, search, guest convention, invitation, email confirmation, version API)
- `routes/settings.php` - Settings-related routes (included in web.php)
- `routes/console.php` - Artisan console commands and scheduled tasks

## Frontend (React + TypeScript)

### Resources (`resources/js/`)

- `actions/` - **Auto-generated** Wayfinder type-safe route actions (DO NOT EDIT)
  - Mirrors backend controller structure
  - Provides type-safe routing from frontend to backend
  
- `components/` - React components
  - `ui/` - Reusable UI components (shadcn/ui style, DO NOT EDIT)
  - `conventions/` - Convention feature components (attendance-report-banner, available-seats-input, convention-card, export-dropdown, floor-row, full-button, occupancy-dropdown, occupancy-gauge, occupancy-indicator, role-badge, section-card, section-modal, user-row)
  - General components (alert-error, app-content, app-header, app-logo, app-logo-icon, app-shell, app-sidebar, app-sidebar-header, appearance-tabs, breadcrumbs, confirmation-dialog, delete-user, heading, input-error, install-prompt, nav-convention, nav-footer, nav-main, nav-user, text-link, two-factor-recovery-codes, two-factor-setup-modal, update-notification-modal, user-info, user-menu-content, version-badge)
  
- `hooks/` - Custom React hooks
  - `use-app-version.ts` - GitHub release version checking
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
  - `app/` - Authenticated app layouts (app-header-layout, app-sidebar-layout)
  - `auth/` - Authentication page layouts (auth-card-layout, auth-simple-layout, auth-split-layout)
  - `settings/` - Settings page layout
  - `app-layout.tsx` - Main app layout wrapper
  - `auth-layout.tsx` - Auth pages layout wrapper
  
- `lib/` - Utility functions
  - `utils.ts` - Common utilities (cn for class merging)
  
- `pages/` - Inertia.js page components
  - `auth/` - Authentication pages (login, confirm-password, forgot-password, reset-password, two-factor-challenge, verify-email, invitation, invitation-invalid, guest-convention-confirmation, guest-convention-invalid, guest-convention-set-password)
  - `conventions/` - Convention pages (index, create, show)
  - `floors/` - Floor pages (index)
  - `sections/` - Section pages (index, show)
  - `search/` - Search pages (index)
  - `users/` - User management pages (index)
  - `settings/` - Settings pages (profile, password, two-factor, appearance)
  - `welcome.tsx` - Landing page
  
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

### Frontend Tests
- `resources/js/hooks/__tests__/` - Hook unit tests (use-attendance-report, use-convention-role, use-occupancy-color)
- `resources/js/components/conventions/__tests__/` - Convention component tests (convention-card, occupancy-dropdown, user-row)
- `resources/js/pages/search/__tests__/` - Search page tests
- `resources/js/test/setup.ts` - Vitest test setup

## Styling (`resources/css/`)
- `app.css` - Main Tailwind CSS entry point

## Public Assets (`public/`)
- `build/` - Compiled frontend assets (auto-generated)
- Static assets (favicon, icons, etc.)

## Tests (`tests/`)

### Backend Tests (Pest PHP)
- `tests/Feature/` - Feature tests organized by domain:
  - `Auth/` - Authentication flows (login, registration, password reset, email verification, 2FA challenge, verification notification)
  - `GuestConventionVerification/` - Guest convention verification tests (confirmation page, set password page, signed URL error handling)
  - `Integration/` - End-to-end integration tests (complete user flows, mobile responsiveness, performance, role-based access, security audit)
  - `Properties/` - Property-based feature tests (attendance periods, convention creation, CSRF, email confirmation, exports, floor/section, occupancy dropdown/full button/metadata, roles, user management)
    - `GuestConventionVerification/` - Guest convention property tests (account activation, confirmation page, existing user auto-login, new user creation, role assignment, password validation, set password page, verification email content)
  - `Section/` - Section-specific tests (authorization)
  - `Settings/` - Settings tests (password update, profile update, 2FA)
  - Root-level feature tests for convention overlap, convention test helper, CSRF, dashboard, exports (data completeness, format serialization), floor/section validation, form errors, input sanitization, rate limiting (login, invitation resend), navigation, password (confirmation, validation), remember me session, search (accessibility, occupancy filter), security (headers, logging), signed URLs, user email validation
- `tests/Property/` - Property-based unit tests (attendance calculations, attendance properties, convention properties, daily occupancy reset, email update confirmation, floor user permissions, invitation email delivery, occupancy color coding, occupancy properties, role-based data scoping, section CRUD property, section frontend property, section user restrictions, section validation property, user properties)
- `tests/Unit/` - Unit tests (attendance reporting, convention creation, exports, occupancy, role-based access, search, user invitation, validation)
- `tests/Helpers/` - Test utilities (ConventionTestHelper)

## Key Conventions

### Backend
- Controllers are organized by feature in subdirectories
- Form requests handle validation logic with SanitizesInput trait
- Actions contain reusable business logic
- Services encapsulate complex domain logic (e.g., AttendanceReportService)
- Concerns provide shared traits
- Policies enforce role-based authorization per model
- Observers handle model lifecycle events (e.g., email confirmation on change)
- Listeners handle application events (e.g., security logging)
- Mail classes use Markdown templates for email rendering

### Frontend
- Components use TypeScript with strict typing
- Wayfinder provides type-safe routing (actions/ and routes/ are auto-generated)
- UI components follow shadcn/ui patterns
- Hooks encapsulate reusable stateful logic
- Pages map 1:1 with Inertia routes
- Modal dialogs used for CRUD operations (e.g., section-modal for create/edit)

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
