# Concerns

## Authorization Gaps

### Convention updates are not policy-protected

`ConventionController::update` updates the model directly with validated input and never calls `authorize('update', $convention)` in `app/Http/Controllers/ConventionController.php:126-130`, even though `ConventionPolicy::update` exists in `app/Policies/ConventionPolicy.php`. The route is only wrapped by `EnsureConventionAccess` and `ScopeByRole` in `routes/web.php:58-61`, so any authenticated convention member who can reach the route can change top-level convention metadata. That is broader than the documented Owner/ConventionUser-only rule and creates a real privilege-escalation path for `FloorUser` and `SectionUser`.

### Section detail and attendance reporting bypass section-level authorization

The standalone section detail route in `routes/web.php:83-88` is not wrapped by `EnsureConventionAccess` or `ScopeByRole`, and `SectionController::show` does not call `$this->authorize('view', $section)` in `app/Http/Controllers/SectionController.php:46-70`. The attendance reporting route in `routes/web.php:109` is also outside convention-access middleware, and `AttendanceController::report` never authorizes the section or validates that the user belongs to the convention in `app/Http/Controllers/AttendanceController.php:64-80`. This leaves UUID-based endpoints depending on obscurity instead of explicit access control.

### Attendance reporting trusts controller-layer checks that do not exist

`AttendanceReportService::reportAttendance` explicitly states that permission validation is deferred to the controller and then proceeds without checking it in `app/Services/AttendanceReportService.php:74-77`. The controller-side check is missing, so the service will accept any authenticated caller the route allows. There is also no guard that the `AttendancePeriod` belongs to the same convention as the `Section`, which means mismatched section/period pairs are not rejected before persistence in `app/Services/AttendanceReportService.php:63-104`. The current test coverage in `tests/Feature/Integration/RoleBasedAccessTest.php:761-775` only exercises the happy path for an assigned section and does not cover hostile combinations.

## Invitation And Verification Risks

### Signed links protect the GET pages but not the password-setting POSTs

The invitation and guest-verification GET routes use `signed` middleware in `routes/web.php:32-34` and `routes/web.php:39-41`, but the POST routes that actually set the password do not in `routes/web.php:35-36` and `routes/web.php:42-43`. The controllers then update the bound `User` directly in `app/Http/Controllers/Auth/GuestConventionVerificationController.php:33-42` and `app/Http/Controllers/Auth/InvitationController.php:32-39`. That means the signed URL is only checked when rendering the form, not when performing the sensitive state change, and the POST handlers do not independently verify the user/convention relationship or token state.

## Data Integrity And Multi-Tenant Boundary Issues

### Cross-convention floor and section IDs are accepted in multiple write paths

`StoreSectionRequest` only validates `floor_id` with `exists:floors,id` in `app/Http/Requests/StoreSectionRequest.php:37-44`, and `SectionController::store` will replace the route-bound floor with whatever `floor_id` was posted in `app/Http/Controllers/SectionController.php:79-92`. The user-management requests likewise accept any existing floor/section UUID in `app/Http/Requests/StoreUserRequest.php:46-49` and `app/Http/Requests/UpdateUserRequest.php:48-51`, and the controller/action insert those IDs directly into pivots in `app/Http/Controllers/UserController.php:138-173` and `app/Actions/InviteUserAction.php:51-70`. Because the pivot tables only constrain the foreign keys independently in `database/migrations/2026_03_05_142548_create_convention_user_pivot_tables.php:45-72`, the database does not enforce that assignments stay inside the current convention. This is a fragile tenant-boundary design that relies entirely on application discipline.

### Search ignores the role-scoping model used elsewhere

The route comment in `routes/web.php:111-113` explicitly says search is available with "no role-based filtering", and `SearchController` returns all available sections in the convention without consulting scoped floor/section IDs in `app/Http/Controllers/SearchController.php:20-50`. That is inconsistent with the rest of the RBAC design, where `ScopeByRole` narrows data returned to `FloorUser` and `SectionUser`. Even if intentional, it is a least-privilege exception that future maintainers can easily miss.

## Product And Workflow Drift

### Invite-user validation contradicts the action implementation

`InviteUserAction` is written to either create a new user or reuse an existing one by email in `app/Actions/InviteUserAction.php:21-34`. `StoreUserRequest`, however, hard-requires a globally unique email with `Rule::unique('users', 'email')` in `app/Http/Requests/StoreUserRequest.php:31-37`. That makes the "invite existing user" branch effectively unreachable through the normal HTTP flow and is a strong sign that validation and business logic have drifted apart.

## Operational And Maintainability Risks

### Role checks are query-heavy and duplicated across the codebase

Every `hasRole` and `hasAnyRole` call performs a fresh database query through `rolesForConvention` in `app/Models/User.php:88-109`. Those methods are then used repeatedly inside middleware, policies, and controllers such as `app/Http/Middleware/EnsureOwnerRole.php`, `app/Http/Middleware/ScopeByRole.php`, `app/Policies/ConventionPolicy.php`, and `app/Policies/SectionPolicy.php`. The design is simple, but it scales poorly because authorization decisions are spread across many call sites with no caching, no dedicated role relation, and no clear place to optimize or instrument later.

### Invitation sending is synchronous and coupled to database transactions

`InviteUserAction` performs the mail send inline inside the surrounding database transaction in `app/Actions/InviteUserAction.php:21-22` and `app/Actions/InviteUserAction.php:73-83`. `UserController::resendInvitation` also sends synchronously in-request in `app/Http/Controllers/UserController.php:233-245`. This couples external mail transport latency and failure handling directly to user-facing requests and, in the transaction case, extends the lifetime of open DB work while waiting on I/O. It is an operational fragility point for invitation-heavy workflows.
