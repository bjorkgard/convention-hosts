# Structure

## Top-Level Layout

Primary application directories:

- `app` - Laravel application code
- `bootstrap` - application bootstrap and cached package/service metadata
- `config` - framework and package configuration
- `database` - migrations, factories, seeders
- `public` - web root, built assets, icons, service worker, manifest
- `resources` - frontend source, CSS, Blade root view
- `routes` - HTTP and scheduled command route definitions
- `storage` - logs, framework state, local files
- `tests` - Pest feature, unit, property, and integration tests

Tooling and project-process folders also exist, but they are not runtime application code:

- `.planning`
- `.agents`
- `.codex`
- `.claude*`
- `.kiro`
- `.qodo`
- `docs`

## Backend Layout

## `app`

`app` is organized by Laravel concern rather than by feature module.

### `app/Actions`

Use-case level workflow classes. These are invoked from controllers and usually own the main transaction boundary for a single command-style operation.

Files here:

- `app/Actions/CreateConventionAction.php`
- `app/Actions/InviteUserAction.php`
- `app/Actions/UpdateOccupancyAction.php`
- `app/Actions/ExportConventionAction.php`

Subfolder:

- `app/Actions/Fortify` for Fortify hook implementations such as user creation and password reset.

### `app/Console`

Console command classes live under `app/Console/Commands`:

- `app/Console/Commands/ResetDailyOccupancy.php`
- `app/Console/Commands/CleanupUnconfirmedGuestConventions.php`

Scheduling is not colocated here; it is declared in `routes/console.php`.

### `app/Concerns`

Small reusable traits shared across requests and user/profile flows:

- `app/Concerns/SanitizesInput.php`
- `app/Concerns/PasswordValidationRules.php`
- `app/Concerns/ProfileValidationRules.php`

### `app/Exports`

Export adapters and sheet builders for XLSX, DOCX, and Markdown output.

Convention export entry points:

- `app/Exports/ConventionExport.php`
- `app/Exports/ConventionWordExport.php`
- `app/Exports/ConventionMarkdownExport.php`

Sheet-style helpers:

- `app/Exports/ConventionSheet.php`
- `app/Exports/FloorsAndSectionsSheet.php`
- `app/Exports/UsersSheet.php`
- `app/Exports/AttendanceHistorySheet.php`

### `app/Http`

HTTP-facing classes are grouped here.

Controllers:

- `app/Http/Controllers` for domain controllers
- `app/Http/Controllers/Auth` for invitation and guest-verification flows
- `app/Http/Controllers/Settings` for profile, password, and 2FA screens

Middleware:

- `app/Http/Middleware/EnsureConventionAccess.php`
- `app/Http/Middleware/EnsureOwnerRole.php`
- `app/Http/Middleware/ScopeByRole.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Middleware/HandleAppearance.php`
- `app/Http/Middleware/SecureHeaders.php`

Requests:

- `app/Http/Requests` for convention domain validation
- `app/Http/Requests/Settings` for settings-specific validation

Responses:

- `app/Http/Responses` for custom Fortify response overrides

Placement convention in this repo:

- controller names match resource nouns, for example `ConventionController`, `FloorController`, `SectionController`
- auth edge cases are kept under `Auth`
- settings pages are isolated under `Settings`
- form requests are named `Store*`, `Update*`, `Report*`, `SetPassword*`, or `Search*`

### `app/Listeners`, `app/Observers`, `app/Mail`

Cross-cutting side effects are separated from controllers:

- security logging: `app/Listeners/SecurityEventListener.php`
- email-change observation: `app/Observers/UserObserver.php`
- outbound mails: `app/Mail/EmailConfirmation.php`, `app/Mail/GuestConventionVerification.php`, `app/Mail/UserInvitation.php`

### `app/Models`

Eloquent models are flat under `app/Models`:

- `Convention`
- `Floor`
- `Section`
- `AttendancePeriod`
- `AttendanceReport`
- `User`

There is no nested domain subfolder structure here.

### `app/Policies`

Authorization policies are one file per protected model or target:

- `app/Policies/ConventionPolicy.php`
- `app/Policies/FloorPolicy.php`
- `app/Policies/SectionPolicy.php`
- `app/Policies/UserPolicy.php`

### `app/Providers`

Provider configuration is intentionally minimal:

- `app/Providers/AppServiceProvider.php` for defaults, security event wiring, observer registration
- `app/Providers/FortifyServiceProvider.php` for auth page rendering, rate limiting, and action bindings

### `app/Services`

Service classes hold process-oriented domain logic that does not fit as a single action:

- `app/Services/AttendanceReportService.php`

## Routes and Bootstrapping

### `bootstrap`

- `bootstrap/app.php` is the main Laravel app entrypoint
- `bootstrap/cache` contains generated framework cache artifacts

### `routes`

Runtime route definitions:

- `routes/web.php` for main web routes
- `routes/settings.php` for settings subroutes
- `routes/console.php` for scheduled jobs

Naming convention in `routes/web.php`:

- convention-scoped resource names use nouns like `conventions.*`, `floors.*`, `sections.*`, `users.*`, `attendance.*`, `search.*`
- unauthenticated signed flows use explicit prefixes like `guest-verification.*` and `invitation.*`

## Database Layout

### `database/migrations`

Schema is migration-driven and largely normalized. Naming follows Laravel timestamp prefixes plus descriptive table names.

Important areas:

- core tables: `create_conventions_table`, `create_floors_table`, `create_sections_table`
- access-control pivots: `create_convention_user_pivot_tables`
- attendance tables: `create_attendance_periods_table`, `create_attendance_reports_table`
- framework tables: cache/jobs/users baseline migrations

### `database/factories`

Factory coverage exists for all core domain models:

- `ConventionFactory`
- `FloorFactory`
- `SectionFactory`
- `AttendancePeriodFactory`
- `AttendanceReportFactory`
- `UserFactory`

### `database/seeders`

- `database/seeders/DatabaseSeeder.php`
- `database/seeders/DemoSeeder.php`

## Frontend Layout

## `resources/js`

The frontend is structured by app concern plus page route.

### Entry points

- `resources/js/app.tsx` - browser boot
- `resources/js/ssr.tsx` - SSR boot

### `resources/js/pages`

Inertia pages map closely to backend routes and controller renders.

Main page groups:

- `resources/js/pages/conventions`
- `resources/js/pages/floors`
- `resources/js/pages/sections`
- `resources/js/pages/users`
- `resources/js/pages/search`
- `resources/js/pages/settings`
- `resources/js/pages/auth`

Placement convention:

- page file names are route-oriented, commonly `index.tsx`, `show.tsx`, `create.tsx`
- auth screens use descriptive names like `guest-convention-set-password.tsx` and `two-factor-challenge.tsx`

### `resources/js/components`

Reusable UI is split into:

- app shell/navigation components at the folder root
- convention-specific widgets under `resources/js/components/conventions`
- base primitives under `resources/js/components/ui`

Examples:

- layout scaffolding: `resources/js/components/app-shell.tsx`, `resources/js/components/app-sidebar.tsx`
- convention feature components: `resources/js/components/conventions/floor-row.tsx`, `resources/js/components/conventions/user-row.tsx`
- primitive controls: `resources/js/components/ui/button.tsx`, `resources/js/components/ui/dialog.tsx`

### `resources/js/layouts`

Layouts are thin wrappers around shell variants:

- `resources/js/layouts/app-layout.tsx`
- `resources/js/layouts/app/*`
- `resources/js/layouts/auth/*`
- `resources/js/layouts/settings/layout.tsx`

Convention:

- app-level pages typically use `app-layout.tsx`
- auth pages choose among specialized auth layouts

### `resources/js/hooks`

Hooks are mostly presentation and page-prop helpers rather than remote-data hooks.

Examples:

- role derivation from Inertia props: `resources/js/hooks/use-convention-role.ts`
- attendance summary derivation: `resources/js/hooks/use-attendance-report.ts`
- UI state helpers: `resources/js/hooks/use-cookie-consent.tsx`, `resources/js/hooks/use-mobile-navigation.ts`

### `resources/js/types`

Shared TS interfaces for page props and domain data:

- `resources/js/types/convention.ts`
- `resources/js/types/user.ts`
- `resources/js/types/auth.ts`
- `resources/js/types/navigation.ts`

### `resources/js/lib`

Small shared utilities, currently lightweight:

- `resources/js/lib/utils.ts`

## Generated Code Areas

These directories are generated and should be treated as derived artifacts, not primary handwritten source:

- `resources/js/actions`
- `resources/js/routes`
- `resources/js/wayfinder`

Why:

- `vite.config.ts` enables `@laravel/vite-plugin-wayfinder`
- generated files include route metadata derived from Laravel controller methods and route definitions
- project instructions explicitly say not to edit generated route/action files manually

Practical convention for handwritten frontend code:

- import route/action helpers from `@/actions/...` or `@/routes/...`
- do not hardcode application URLs in page or component code

## Public and Asset Layout

### `public`

Contains runtime-served static files and build output:

- `public/build` - Vite build artifacts
- `public/icons` - PWA icon assets
- `public/manifest.json` - PWA manifest
- `public/sw.js` - service worker

### `resources/css`

- `resources/css/app.css` is the main stylesheet entry consumed by Vite

### `resources/views`

- `resources/views/app.blade.php` is the Blade root view used by Inertia

## Test Layout

Tests are organized by scope and style under `tests`:

- `tests/Feature` - HTTP and end-to-end behavior
- `tests/Feature/Auth` - auth flow coverage
- `tests/Feature/Integration` - cross-system scenarios
- `tests/Feature/Properties` - feature-level property tests
- `tests/Property` - pure property-based tests
- `tests/Unit` - unit coverage for actions/services/validation
- `tests/Helpers` - shared setup helpers such as `tests/Helpers/ConventionTestHelper.php`

Framework test entry files:

- `tests/Pest.php`
- `tests/TestCase.php`

Frontend test placement:

- component tests colocated in `resources/js/components/**/__tests__`
- hook tests under `resources/js/hooks/__tests__`
- page tests under `resources/js/pages/**/__tests__`

## Naming and Placement Conventions

- Laravel model names are singular and map directly to domain concepts.
- Controllers remain shallow and are grouped by HTTP concern, not domain module folders.
- Validation classes are request-specific and live under `app/Http/Requests`.
- Reusable business workflows go into `app/Actions` or `app/Services`, not controllers.
- Feature-heavy React components are grouped in `resources/js/components/conventions`.
- Route-facing UI stays in `resources/js/pages`, not `components`.
- Generated Wayfinder code lives separately from handwritten page/component code.
- Tests are split by execution level and by backend/frontend context rather than mirroring every source directory.
