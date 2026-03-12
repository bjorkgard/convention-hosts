# Code Conventions

## Stack And Style Baseline

- Backend is Laravel 12 on PHP 8.2 with business logic split across `app/Actions`, `app/Services`, `app/Http/*`, and Eloquent models in `app/Models`.
- Frontend is Inertia.js + React 19 + TypeScript under `resources/js`, bundled with Vite in `vite.config.ts`.
- PHP formatting is enforced by Laravel Pint through `composer lint` and `composer lint:check` in `composer.json`.
- Frontend formatting is enforced by Prettier in `.prettierrc`: 4-space indentation, semicolons, single quotes, `printWidth: 80`, and Tailwind class sorting.
- ESLint in `eslint.config.js` enforces import ordering and `@typescript-eslint/consistent-type-imports`; generated files and shadcn UI primitives are intentionally ignored.
- TypeScript is strict enough to matter: `strict: true` and `noImplicitAny: true` are set in `tsconfig.json`, although ESLint explicitly disables `no-explicit-any`.

## Backend Conventions

## Laravel Structure

- Controllers in `app/Http/Controllers` stay thin and mostly orchestrate request validation, authorization, action/service calls, and Inertia/redirect responses.
- Validation lives in Form Requests under `app/Http/Requests`; request classes often use `withValidator()` for cross-field or domain-specific rules.
- Shared input sanitization is centralized in `app/Concerns/SanitizesInput.php` and mixed into request classes such as `app/Http/Requests/StoreConventionRequest.php` and `app/Http/Requests/StoreUserRequest.php`.
- Business workflows move into action/service classes such as `app/Actions/CreateConventionAction.php`, `app/Actions/UpdateOccupancyAction.php`, and `app/Services/AttendanceReportService.php`.
- Authorization is layered:
  - route middleware in `routes/web.php`
  - middleware classes such as `app/Http/Middleware/EnsureConventionAccess.php`, `app/Http/Middleware/EnsureOwnerRole.php`, and `app/Http/Middleware/ScopeByRole.php`
  - policies in `app/Policies`

## Naming And API Shape

- Classes use singular nouns and action-oriented names: `Convention`, `Floor`, `Section`, `CreateConventionAction`, `ReportAttendanceRequest`.
- Methods favor Laravel defaults: `index`, `show`, `store`, `update`, `destroy`.
- Action classes consistently expose an `execute(...)` method instead of `handle(...)`.
- Eloquent relationships use descriptive camelCase names: `attendancePeriods()`, `lastUpdatedBy()`, `rolesForConvention()`.
- Domain role strings are treated as canonical literals across app and tests: `Owner`, `ConventionUser`, `FloorUser`, `SectionUser`.

## Data Access And Transactions

- Multi-table writes are usually wrapped in `DB::transaction(...)`; see `app/Actions/CreateConventionAction.php` and `app/Http/Controllers/UserController.php`.
- The codebase mixes Eloquent relations with direct `DB::table(...)` access for pivot-heavy role logic. That is the established pattern for convention/floor/section assignment tables.
- Controllers and services explicitly eager-load related data to avoid N+1 regressions; representative examples are `app/Http/Controllers/ConventionController.php` and `app/Http/Controllers/UserController.php`.
- UUIDs are standard on core models through `HasUuids`; see `app/Models/Convention.php`, `app/Models/User.php`, and `app/Models/Section.php`.

## Validation And Sanitization

- Request rules are generally expressed in array form rather than pipe-delimited strings.
- Simple per-field validation lives in `rules()`, while dependent validation is appended in `withValidator()`.
- Sanitization happens before validation via `prepareForValidation()` from `app/Concerns/SanitizesInput.php`.
- Rich-text fields must opt in via `richTextFields()`; otherwise string input is trimmed and stripped of HTML.
- Password-like fields are excluded from sanitization by default in `excludedFromSanitization()`.

## Error Handling Norms

- Normal user-facing failures prefer Laravel validation errors, `authorize(...)` failures, signed-route middleware responses, or redirects with flash messages.
- Success/error flash messages are passed through Inertia shared props in `app/Http/Middleware/HandleInertiaRequests.php`.
- Some domain services still throw generic `\Exception` strings for business rule violations, especially in `app/Services/AttendanceReportService.php`. That is an observed convention, but it is a weaker pattern than typed exceptions.
- Security headers are applied centrally in `app/Http/Middleware/SecureHeaders.php`.

## Frontend Conventions

## React + Inertia Patterns

- Pages live under `resources/js/pages` and map to backend routes by Inertia component name, for example `Inertia::render('conventions/show', ...)` in `app/Http/Controllers/ConventionController.php` maps to `resources/js/pages/conventions/show.tsx`.
- Layout composition is explicit through wrappers like `resources/js/layouts/app-layout.tsx`.
- Page components usually:
  - import Wayfinder action/route helpers from `@/actions/...` or `@/routes/...`
  - declare local prop interfaces
  - define small helper functions near the top of the file
  - use `router.get/post/delete(...)` for imperative navigation and mutations
- Forms prefer Inertia `<Form>` and generated `.form()` helpers; see `resources/js/pages/conventions/create.tsx`.
- Shared server props are consumed through hooks such as `resources/js/hooks/use-attendance-report.ts`, `resources/js/hooks/use-convention-role.ts`, and `resources/js/hooks/use-flash-toast.ts`.

## TypeScript And Imports

- Use path aliases from `tsconfig.json`: imports typically use `@/components/...`, `@/hooks/...`, `@/types/...`.
- Type-only imports are preferred where possible because ESLint enforces consistent type import style.
- Components and hooks usually define explicit local interfaces/types rather than relying on inferred `any`.
- Utility composition follows the `cn(...)` helper in `resources/js/lib/utils.ts`.

## UI And Styling

- UI primitives under `resources/js/components/ui` are shadcn-based and largely treated as vendor/generated code.
- Tailwind v4 styles live in `resources/css/app.css` and are token-driven through CSS variables and theme overrides.
- Feature components are grouped by domain, for example `resources/js/components/conventions`.
- Class strings are long but consistently sorted by Prettier + `prettier-plugin-tailwindcss`.
- The codebase uses a mix of authored components and generated shadcn files. Existing generated UI files such as `resources/js/components/ui/button.tsx` use 2-space formatting and should generally be left to upstream tooling unless necessary.

## Generated Code Rules

- `resources/js/actions/**` and `resources/js/routes/**` are generated by Wayfinder and should not be edited manually.
- Regenerate frontend route helpers with `php artisan wayfinder:generate --with-form` after backend route/controller signature changes.
- ESLint ignores `resources/js/actions/**`, `resources/js/routes/**`, and `resources/js/components/ui/*`, which is a strong signal that these areas are not intended for hand-maintained style cleanup.

## Architectural Patterns

## Role-Scoped Convention Domain

- Route groups in `routes/web.php` encode the authorization boundary before controller logic runs.
- Role scoping is pushed into request context by `app/Http/Middleware/ScopeByRole.php`, then consumed inside controllers to filter queries.
- Policies still gate update/delete/export actions, especially for standalone routes like `sections/{section}` and owner-only convention actions.

## Inertia As The Delivery Boundary

- Server controllers render pages with already-shaped props instead of exposing a separate JSON API for the React app.
- Shared global state such as authenticated user, sidebar state, flash messages, and app version is injected in `app/Http/Middleware/HandleInertiaRequests.php`.
- Search, attendance, and management flows favor route-driven state over client-side data fetching libraries.

## Practical Quality Notes

- The repository is conventionally strong on request validation, eager loading, and route structure.
- The main convention debt visible in code is inconsistency between highly structured request/controller code and looser domain error handling in services that throw bare `\Exception`.
- There is also a pragmatic but mixed persistence style: Eloquent for core aggregates, raw query builder for pivot/role mechanics. New code should follow the local pattern instead of trying to normalize the entire repository ad hoc.
