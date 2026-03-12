# Architecture

## System Shape

Convention Hosts is a Laravel 12 monolith with an Inertia.js + React 19 frontend. The backend owns routing, authorization, validation, persistence, scheduled jobs, and exports; the frontend renders page components and uses generated Wayfinder route helpers to call backend endpoints.

Core runtime entry points:

- HTTP app bootstrap: `bootstrap/app.php`
- Web routes: `routes/web.php`
- Settings routes: `routes/settings.php`
- Scheduled tasks: `routes/console.php`
- Browser app entry: `resources/js/app.tsx`
- SSR entry: `resources/js/ssr.tsx`
- Vite + Wayfinder generation: `vite.config.ts`

## Architectural Layers

### 1. Bootstrapping and cross-cutting middleware

`bootstrap/app.php` wires the web route file, console schedule file, `/up` health endpoint, and the shared web middleware stack:

- `App\Http\Middleware\SecureHeaders`
- `App\Http\Middleware\HandleAppearance`
- `App\Http\Middleware\HandleInertiaRequests`
- `Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets`

This is also where invalid signed URLs are converted into Inertia error pages for invitation and guest-verification flows instead of plain Laravel errors.

### 2. Routing and controller layer

HTTP routes are declared centrally in `routes/web.php` and grouped by authentication and convention access requirements. Controllers under `app/Http/Controllers` are intentionally thin:

- render Inertia pages
- call actions/services
- authorize via policies or role checks
- redirect with flash messages

Convention domain controllers:

- `app/Http/Controllers/ConventionController.php`
- `app/Http/Controllers/FloorController.php`
- `app/Http/Controllers/SectionController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Controllers/SearchController.php`

Auth and guest flows live separately under:

- `app/Http/Controllers/Auth/InvitationController.php`
- `app/Http/Controllers/Auth/GuestConventionVerificationController.php`
- `app/Http/Controllers/GuestConventionController.php`

Settings concerns are isolated under `app/Http/Controllers/Settings`.

### 3. Request validation layer

Validation sits in form requests under `app/Http/Requests` and `app/Http/Requests/Settings`. These classes handle request shape and some domain-adjacent invariants before controller logic runs.

Examples:

- overlapping convention dates/location: `app/Http/Requests/StoreConventionRequest.php`
- role-dependent assignment requirements: `app/Http/Requests/StoreUserRequest.php`
- occupancy input normalization gate: `app/Http/Requests/UpdateOccupancyRequest.php`
- search filter validation: `app/Http/Requests/SearchRequest.php`

Shared sanitization is pushed into `app/Concerns/SanitizesInput.php`, which keeps request classes consistent.

### 4. Domain workflows: actions and services

Business workflows are split between `app/Actions` and `app/Services`.

Actions encapsulate discrete use cases:

- convention creation and initial role setup: `app/Actions/CreateConventionAction.php`
- invitation flow and role assignment: `app/Actions/InviteUserAction.php`
- occupancy normalization and metadata stamping: `app/Actions/UpdateOccupancyAction.php`
- export orchestration: `app/Actions/ExportConventionAction.php`

Service classes handle multi-step domain processes with internal lifecycle rules:

- attendance lifecycle: `app/Services/AttendanceReportService.php`

Practical split in this codebase:

- `Actions` are controller-triggered transactional operations for one use case.
- `Services` model reusable domain processes with branching rules and internal state transitions.

### 5. Persistence and domain model layer

The domain model is Eloquent-based, with UUID primary keys on the main domain tables and explicit pivot tables for role scoping.

Primary models:

- `app/Models/Convention.php`
- `app/Models/Floor.php`
- `app/Models/Section.php`
- `app/Models/AttendancePeriod.php`
- `app/Models/AttendanceReport.php`
- `app/Models/User.php`

Key relationships:

- Convention -> many Floors
- Floor -> many Sections
- Convention <-> many Users via `convention_user`
- Convention/User/Role via `convention_user_roles`
- Floor/User via `floor_user`
- Section/User via `section_user`
- Convention -> many AttendancePeriods
- AttendancePeriod -> many AttendanceReports

Schema definition is centralized in `database/migrations`, especially:

- `database/migrations/2026_03_05_142439_create_conventions_table.php`
- `database/migrations/2026_03_05_142455_create_floors_table.php`
- `database/migrations/2026_03_05_142512_create_sections_table.php`
- `database/migrations/2026_03_05_142548_create_convention_user_pivot_tables.php`
- `database/migrations/2026_03_05_142607_create_attendance_periods_table.php`
- `database/migrations/2026_03_05_142623_create_attendance_reports_table.php`

Notable persistence rules enforced in schema:

- one convention membership per user: `convention_user`
- one role tuple per user/convention/role: `convention_user_roles`
- one attendance period per convention/date/period
- one attendance report per section per attendance period

## Domain Boundaries

### Convention management

The main bounded context is convention administration: create conventions, update metadata, manage nested floors and sections, and export data. The flow centers on `ConventionController`, `FloorController`, `SectionController`, and the `Convention`, `Floor`, and `Section` models.

### Role and access management

Authorization is a first-class subdomain, implemented without a package. It spans:

- route middleware: `app/Http/Middleware/EnsureConventionAccess.php`, `app/Http/Middleware/EnsureOwnerRole.php`, `app/Http/Middleware/ScopeByRole.php`
- policies: `app/Policies/*.php`
- pivot-backed role lookup helpers on `app/Models/User.php` and `app/Models/Convention.php`

This is both a security boundary and a data-shaping boundary, because `ScopeByRole` injects request-level scoping used by controllers to filter floors, sections, and users.

### Attendance reporting

Attendance is modeled as a lifecycle around `AttendancePeriod` and `AttendanceReport`, not as fields on sections. `app/Services/AttendanceReportService.php` owns period creation, lock semantics, and reporter update rules. `app/Http/Controllers/AttendanceController.php` is a thin adapter over that service.

### Occupancy tracking

Section occupancy is mutable operational data stored directly on `sections`. `app/Actions/UpdateOccupancyAction.php` normalizes three UI input modes into canonical `occupancy` and `available_seats` fields and records audit metadata via `last_occupancy_updated_by` and `last_occupancy_updated_at`.

### Guest onboarding and invitations

Unauthenticated and pre-authenticated onboarding is separated from standard Fortify flows:

- guest-created conventions: `app/Http/Controllers/GuestConventionController.php`
- signed guest verification: `app/Http/Controllers/Auth/GuestConventionVerificationController.php`
- signed invitation acceptance: `app/Http/Controllers/Auth/InvitationController.php`
- email side effects: `app/Mail/*.php`
- email-change confirmation: `app/Observers/UserObserver.php`

### Exporting

Export is its own workflow boundary. `app/Actions/ExportConventionAction.php` eager-loads the full tree and delegates to exporters under `app/Exports`:

- spreadsheet aggregation: `app/Exports/ConventionExport.php`
- word export: `app/Exports/ConventionWordExport.php`
- markdown export: `app/Exports/ConventionMarkdownExport.php`

## Request and Data Flow

## HTTP page/render flow

1. Request enters Laravel through `bootstrap/app.php`.
2. Web middleware adds security headers, appearance state, and shared Inertia props.
3. `routes/web.php` or `routes/settings.php` matches the route.
4. Convention-scoped middleware may assert access and inject floor/section scope.
5. Controller validates through a form request, authorizes, and loads domain data.
6. Controller returns `Inertia::render(...)` with page props.
7. `resources/js/app.tsx` resolves `resources/js/pages/**/*.tsx` and hydrates the page.

Shared props are added in `app/Http/Middleware/HandleInertiaRequests.php`:

- authenticated user
- app name
- sidebar state
- app version
- flash messages

## Mutation flow

Typical write path:

1. React page or component calls a generated action helper from `resources/js/actions/...`.
2. Request hits controller route in `routes/web.php`.
3. Form request validates and sanitizes input.
4. Controller authorizes with policy or explicit role checks.
5. Action/service performs transactional updates.
6. Controller redirects back or to another page with session flash.
7. Inertia refreshes affected props on the next response.

Examples:

- create convention: `resources/js/pages/conventions/create.tsx` -> `app/Http/Controllers/ConventionController.php` -> `app/Actions/CreateConventionAction.php`
- update occupancy: `resources/js/pages/sections/show.tsx` -> `app/Http/Controllers/SectionController.php` -> `app/Actions/UpdateOccupancyAction.php`
- report attendance: `resources/js/pages/sections/show.tsx` -> `app/Http/Controllers/AttendanceController.php` -> `app/Services/AttendanceReportService.php`

## Authorization flow

Authorization is layered, not centralized in one mechanism:

1. route middleware enforces broad convention membership or owner-only access
2. `ScopeByRole` narrows visible IDs for some listing routes
3. controller methods call `$this->authorize(...)` where action-level checks matter
4. policies evaluate role and assignment pivots

This combination prevents both unauthorized entry and over-broad dataset reads.

## Frontend architecture

The frontend is page-oriented rather than API-client oriented. Inertia pages under `resources/js/pages` map closely to Laravel controllers and route names. The frontend primarily consumes server props instead of building a separate client-side data fetching layer.

Key frontend building blocks:

- page components: `resources/js/pages`
- layout shells: `resources/js/layouts`
- reusable feature components: `resources/js/components`
- base UI primitives: `resources/js/components/ui`
- small client-side derivation hooks: `resources/js/hooks`
- TS contracts for props/domain data: `resources/js/types`

Route generation is important here:

- generated TS action wrappers: `resources/js/actions`
- generated TS route wrappers: `resources/js/routes`
- generation runtime helpers: `resources/js/wayfinder`

Pages import helpers like `@/actions/App/Http/Controllers/ConventionController` instead of hardcoding URLs, which keeps PHP route definitions and TS callers aligned.

## Important Abstractions

### Role-aware scoping through pivots

Instead of a third-party RBAC package, this codebase uses pivot tables plus model helper methods:

- `User::rolesForConvention(...)`
- `User::hasRole(...)`
- `User::hasAnyRole(...)`
- `Convention::userRoles(...)`

That same role model also drives data visibility via `ScopeByRole`.

### Inertia shared state

`app/Http/Middleware/HandleInertiaRequests.php` is the shared contract layer between Laravel and React. Global props and flash state should typically enter the frontend there, not via ad hoc duplication.

### Wayfinder-generated navigation/mutations

`vite.config.ts` enables the Wayfinder plugin, and generated files in `resources/js/actions` and `resources/js/routes` expose typed route/action builders. These generated helpers are a core architectural seam between backend route definitions and frontend calls.

### Fortify adapted to Inertia

Authentication is not implemented as standalone custom controllers everywhere; Fortify is configured in `app/Providers/FortifyServiceProvider.php` to render Inertia pages and use custom response classes:

- `app/Http/Responses/LoginResponse.php`
- `app/Http/Responses/TwoFactorLoginResponse.php`

### Scheduled operational maintenance

Operational consistency relies on scheduled commands:

- occupancy reset: `app/Console/Commands/ResetDailyOccupancy.php`
- stale guest cleanup: `app/Console/Commands/CleanupUnconfirmedGuestConventions.php`

Those schedules are declared in `routes/console.php`, so some business behavior is time-driven rather than request-driven.
